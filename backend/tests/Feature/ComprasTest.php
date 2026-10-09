<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\vidraria;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComprasTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnidadeMedidaSeeder::class);
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico']);
        Sanctum::actingAs($this->tecnico);
    }

    /** Reagente com 2 kg em estoque e 6 kg consumidos nos últimos 12 meses (0,5 kg/mês). */
    private function reagenteComConsumo(float $estoqueFinal = 2): array
    {
        $r = $this->postJson('/api/reagentes', [
            'nome' => 'Percarbonato de Sódio', 'catmat' => '123', 'idunidademedida' => 1,
            'quantidade' => 6 + $estoqueFinal, 'lote' => 'LOT-A', 'data_validade' => now()->addYears(2)->toDateString(),
        ])->json();

        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'],
            'quantidade' => 6, 'data' => now()->subMonths(3)->toDateString(),
        ])->assertCreated();

        return $r;
    }

    private function itemDaPrevisao(string $query = 'margem=0'): array
    {
        return $this->getJson("/api/compras/previsao?meses_base=12&meses_cobrir=12&{$query}")
            ->assertOk()->json('itens.0');
    }

    public function test_previsao_calcula_media_e_quantidade_sugerida(): void
    {
        $this->reagenteComConsumo(2);

        $item = $this->itemDaPrevisao('margem=0');

        $this->assertEquals(0.5, $item['media_mensal']);
        $this->assertEquals(6, $item['necessidade']);   // 0,5 × 12
        $this->assertEquals(4, $item['sugerido']);      // 6 − 2 em estoque
        $this->assertEquals(4, $item['cobertura_meses']);
        $this->assertStringContainsString('Consumo de 6 kg nos últimos 12 meses', $item['justificativa']);
    }

    public function test_margem_de_seguranca_aumenta_a_sugestao(): void
    {
        $this->reagenteComConsumo(2);

        // 6 × 1,2 = 7,2 − 2 = 5,2
        $this->assertEquals(5.2, $this->itemDaPrevisao('margem=20')['sugerido']);
    }

    public function test_estoque_que_nao_dura_ate_a_compra_chegar_e_pedir_agora(): void
    {
        $this->reagenteComConsumo(2); // dura 4 meses; compra leva 6 (padrão)

        $this->assertEquals('pedir_agora', $this->itemDaPrevisao()['situacao']);
    }

    public function test_pedido_em_andamento_desconta_da_sugestao(): void
    {
        $r = $this->reagenteComConsumo(2);
        $this->postJson('/api/pedidoscompra', ['idreagente' => $r['idreagente'], 'quantidade' => 4])->assertCreated();

        $item = $this->itemDaPrevisao('margem=0');
        $this->assertEquals(4, $item['em_pedido']);
        $this->assertEquals(0, $item['sugerido']);
        $this->assertEquals('em_pedido', $item['situacao']);
    }

    public function test_lote_vencido_nao_conta_como_estoque(): void
    {
        $r = $this->reagenteComConsumo(2);
        $this->postJson('/api/entradareagentes', [
            'idreagente' => $r['idreagente'], 'quantidade' => 10, 'lote' => 'VENCIDO',
            'data_validade' => now()->subDay()->toDateString(),
        ])->assertCreated();

        $this->assertEquals(2, $this->itemDaPrevisao()['estoque']);
    }

    public function test_vidraria_usa_baixas_aprovadas_e_sugere_unidades_inteiras(): void
    {
        $bequer = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 10]);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->postJson('/api/saidavidrarias', [
            'idvidraria' => $bequer->idvidraria, 'quantidade' => 5, 'motivo' => 'Quebra',
            'observacao' => 'Quebras ao longo do semestre',
        ])->assertCreated(); // admin: aprovada na hora; sobram 5

        $item = $this->getJson('/api/compras/previsao?categoria=vidrarias&meses_base=12&meses_cobrir=12&margem=10')->json('itens.0');

        // 5 × 1,1 = 5,5 − 5 = 0,5 → arredonda para 1 unidade
        $this->assertEquals(1, $item['sugerido']);
    }

    public function test_fluxo_do_pedido_ate_o_recebimento_registra_lote(): void
    {
        $r = $this->reagenteComConsumo(2);
        $id = $this->postJson('/api/pedidoscompra', ['idreagente' => $r['idreagente'], 'quantidade' => 5])
            ->assertCreated()->assertJsonPath('status', 'solicitado')->json('idpedido');

        $this->putJson("/api/pedidoscompra/{$id}", ['status' => 'em_licitacao'])->assertOk();
        $this->putJson("/api/pedidoscompra/{$id}", ['status' => 'aguardando_entrega', 'previsao_entrega' => now()->addMonth()->toDateString()])->assertOk();

        // Reagente exige lote e validade no recebimento
        $this->postJson("/api/pedidoscompra/{$id}/receber", ['quantidade' => 5])
            ->assertStatus(422)->assertJsonValidationErrors(['lote', 'data_validade']);

        $this->postJson("/api/pedidoscompra/{$id}/receber", [
            'quantidade' => 5, 'lote' => 'LOT-NOVO', 'data_validade' => now()->addYears(3)->toDateString(),
        ])->assertOk()->assertJsonPath('status', 'recebido');

        $reagente = $this->getJson("/api/reagentes/{$r['idreagente']}")->json();
        $this->assertEquals(7, (float) $reagente['quantidade']);
        $this->assertContains('LOT-NOVO', array_column($reagente['lotes'], 'lote'));
    }

    public function test_recebimento_de_vidraria_soma_ao_estoque(): void
    {
        $bequer = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 3]);
        $id = $this->postJson('/api/pedidoscompra', ['idvidraria' => $bequer->idvidraria, 'quantidade' => 10])->json('idpedido');

        $this->postJson("/api/pedidoscompra/{$id}/receber", ['quantidade' => 10])->assertOk();
        $this->assertEquals(13, $bequer->fresh()->quantidade);
    }

    public function test_pedido_recebido_nao_pode_mudar_nem_ser_recebido_de_novo(): void
    {
        $bequer = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 3]);
        $id = $this->postJson('/api/pedidoscompra', ['idvidraria' => $bequer->idvidraria, 'quantidade' => 2])->json('idpedido');
        $this->postJson("/api/pedidoscompra/{$id}/receber", ['quantidade' => 2])->assertOk();

        $this->postJson("/api/pedidoscompra/{$id}/receber", ['quantidade' => 2])->assertStatus(422);
        $this->putJson("/api/pedidoscompra/{$id}", ['status' => 'cancelado'])->assertStatus(422);
        $this->assertEquals(5, $bequer->fresh()->quantidade);
    }

    public function test_pedido_precisa_de_um_item_so(): void
    {
        $this->postJson('/api/pedidoscompra', ['quantidade' => 1])
            ->assertStatus(422)->assertJsonValidationErrors(['idreagente', 'idvidraria']);
    }

    public function test_perfil_consulta_ve_mas_nao_cria_pedido(): void
    {
        $bequer = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 3]);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'consulta']));

        $this->getJson('/api/compras/previsao')->assertOk();
        $this->getJson('/api/pedidoscompra')->assertOk();
        $this->postJson('/api/pedidoscompra', ['idvidraria' => $bequer->idvidraria, 'quantidade' => 1])->assertForbidden();
    }

    public function test_dashboard_avisa_itens_para_pedir_agora(): void
    {
        $this->reagenteComConsumo(2);

        $this->getJson('/api/dashboard')
            ->assertJsonPath('compras_pedir_agora', 1)
            ->assertJsonPath('compras_pedir_agora_itens.0', 'Percarbonato de Sódio');
    }
}
