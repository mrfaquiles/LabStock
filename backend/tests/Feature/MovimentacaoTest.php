<?php

namespace Tests\Feature;

use App\Models\equipamento;
use App\Models\laboratorio;
use App\Models\reagente;
use App\Models\User;
use App\Models\vidraria;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;
    private laboratorio $lab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnidadeMedidaSeeder::class);
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico']);
        $this->lab = laboratorio::create(['nome' => 'Lab de Química Geral']);
        Sanctum::actingAs($this->tecnico);
    }

    /** Cadastra pela API (cria também o lote inicial) e devolve o JSON do reagente. */
    private function reagente(array $extra = []): array
    {
        return $this->postJson('/api/reagentes', array_merge([
            'nome' => 'Percarbonato de Sódio',
            'catmat' => '123456',
            'idunidademedida' => 1, // kg
            'quantidade' => 2,
            'lote' => 'LOT-A',
            'data_validade' => now()->addYear()->toDateString(),
        ], $extra))->assertCreated()->json();
    }

    private function novoLote(int $idreagente, float $quantidade, string $lote, string $validade): array
    {
        return $this->postJson('/api/entradareagentes', [
            'idreagente' => $idreagente,
            'quantidade' => $quantidade,
            'lote' => $lote,
            'data_validade' => $validade,
        ])->assertCreated()->json();
    }

    private function estoque(int $idreagente): float
    {
        return (float) reagente::find($idreagente)->quantidade;
    }

    // ---------- Cadastro de reagentes ----------

    public function test_cadastro_de_reagente_exige_catmat_lote_e_validade(): void
    {
        $this->postJson('/api/reagentes', ['nome' => 'Sem dados', 'idunidademedida' => 1, 'quantidade' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['catmat', 'lote', 'data_validade']);
    }

    public function test_quantidade_negativa_e_recusada(): void
    {
        $this->postJson('/api/reagentes', [
            'nome' => 'Etanol', 'catmat' => '1', 'idunidademedida' => 2, 'quantidade' => -3,
            'lote' => 'L', 'data_validade' => now()->addYear()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['quantidade']);
    }

    public function test_cadastro_cria_lote_inicial_com_fracao_decimal(): void
    {
        $r = $this->reagente(['quantidade' => 0.5]);

        $this->assertEquals(4, $r['meses_alerta']);
        $this->assertEquals('kg', $r['unidade_medida']['sigla']);
        $this->assertCount(1, $r['lotes']);
        $this->assertEquals('LOT-A', $r['lotes'][0]['lote']);
        $this->assertEquals(0.5, $r['lotes'][0]['saldo']);
    }

    public function test_edicao_nao_altera_quantidade_diretamente(): void
    {
        $r = $this->reagente(['quantidade' => 2]);

        $this->putJson("/api/reagentes/{$r['idreagente']}", ['nome' => 'Percarbonato de Sódio P.A.', 'quantidade' => 50])
            ->assertOk()
            ->assertJsonPath('nome', 'Percarbonato de Sódio P.A.');
        $this->assertEquals(2, $this->estoque($r['idreagente']));
    }

    public function test_corrigir_lote_no_cadastro_corrige_o_lote_inicial(): void
    {
        $r = $this->reagente();

        $atualizado = $this->putJson("/api/reagentes/{$r['idreagente']}", ['lote' => 'LOT-A-CORRIGIDO'])
            ->assertOk()->json();
        $this->assertEquals('LOT-A-CORRIGIDO', $atualizado['lotes'][0]['lote']);
    }

    // ---------- Uso de reagente por lote ----------

    public function test_uso_desconta_do_lote_escolhido_e_do_total(): void
    {
        $r = $this->reagente(['quantidade' => 2]); // LOT-A: 2 kg
        $loteB = $this->novoLote($r['idreagente'], 3, 'LOT-B', now()->addYears(2)->toDateString());
        $this->assertEquals(5, $this->estoque($r['idreagente']));

        // Usou 500 g (0.5 kg) do lote B
        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'],
            'identrada' => $loteB['identradareagente'],
            'quantidade' => 0.5,
            'observacao' => 'Aula prática de oxidação',
        ])->assertCreated()->assertJsonPath('usuario.id', $this->tecnico->id);

        $this->assertEquals(4.5, $this->estoque($r['idreagente']));
        $lotes = collect($this->getJson("/api/reagentes/{$r['idreagente']}")->json('lotes'))->keyBy('lote');
        $this->assertEquals(2, $lotes['LOT-A']['saldo']);
        $this->assertEquals(2.5, $lotes['LOT-B']['saldo']);
    }

    public function test_uso_exige_lote(): void
    {
        $r = $this->reagente();

        $this->postJson('/api/saidareagentes', ['idreagente' => $r['idreagente'], 'quantidade' => 0.1])
            ->assertStatus(422)->assertJsonValidationErrors(['identrada']);
    }

    public function test_uso_maior_que_saldo_do_lote_e_recusado_mesmo_com_estoque_total(): void
    {
        $r = $this->reagente(['quantidade' => 1]); // LOT-A: 1 kg
        $this->novoLote($r['idreagente'], 5, 'LOT-B', now()->addYears(2)->toDateString()); // total 6 kg

        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'],
            'identrada' => $r['lotes'][0]['identradareagente'],
            'quantidade' => 1.5,
        ])->assertStatus(422)->assertJsonPath('message', 'O lote LOT-A tem apenas 1 kg disponível.');

        $this->assertEquals(6, $this->estoque($r['idreagente']));
    }

    public function test_lote_esgotado_some_da_lista_de_lotes(): void
    {
        $r = $this->reagente(['quantidade' => 1]);

        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 1,
        ])->assertCreated();

        $this->getJson("/api/reagentes/{$r['idreagente']}")->assertJsonCount(0, 'lotes');
    }

    public function test_uso_rejeita_lote_de_outro_reagente(): void
    {
        $a = $this->reagente();
        $b = $this->reagente(['nome' => 'Outro']);

        $this->postJson('/api/saidareagentes', [
            'idreagente' => $a['idreagente'], 'identrada' => $b['lotes'][0]['identradareagente'], 'quantidade' => 0.1,
        ])->assertStatus(422)->assertJsonPath('message', 'O lote informado não pertence a este reagente.');
    }

    public function test_nova_entrada_de_lote_exige_lote_e_validade(): void
    {
        $r = $this->reagente();

        $this->postJson('/api/entradareagentes', ['idreagente' => $r['idreagente'], 'quantidade' => 1])
            ->assertStatus(422)->assertJsonValidationErrors(['lote', 'data_validade']);
    }

    public function test_estorno_de_uso_devolve_estoque(): void
    {
        $r = $this->reagente(['quantidade' => 5]);
        $id = $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 2,
        ])->json('idsaidareagente');

        $this->deleteJson("/api/saidareagentes/{$id}")->assertOk();
        $this->assertEquals(5, $this->estoque($r['idreagente']));
    }

    public function test_edicao_de_movimentacao_altera_apenas_observacao(): void
    {
        $r = $this->reagente(['quantidade' => 5]);
        $id = $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 2,
        ])->json('idsaidareagente');

        $this->putJson("/api/saidareagentes/{$id}", ['quantidade' => 4, 'observacao' => 'Aula de titulação'])
            ->assertOk()
            ->assertJsonPath('observacao', 'Aula de titulação');
        $this->assertEquals(3, $this->estoque($r['idreagente']));
    }

    public function test_reagente_sem_uso_pode_ser_excluido_com_seus_lotes(): void
    {
        $r = $this->reagente();

        $this->deleteJson("/api/reagentes/{$r['idreagente']}")->assertOk();
        $this->assertNull(reagente::find($r['idreagente']));
    }

    public function test_reagente_com_uso_nao_pode_ser_excluido(): void
    {
        $r = $this->reagente();
        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 0.1,
        ])->assertCreated();

        $this->deleteJson("/api/reagentes/{$r['idreagente']}")->assertStatus(409);
    }

    // ---------- Vidrarias e equipamentos ----------

    public function test_vidraria_exige_catmat(): void
    {
        $this->postJson('/api/vidrarias', ['nome' => 'Béquer 250 ml'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['catmat']);
    }

    public function test_entrada_de_equipamento_atualiza_localizacao(): void
    {
        $equip = equipamento::create(['nome' => 'Balança Analítica', 'catmat' => '999', 'quantidade' => 0]);

        $this->postJson('/api/entradaequipamentos', [
            'idequipamento' => $equip->idequipamento,
            'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 1,
            'observacao' => 'Bancada 03',
        ])->assertCreated();

        $this->assertEquals('Lab de Química Geral - Bancada 03', $equip->fresh()->localizacao);
    }

    public function test_vidraria_com_historico_nao_pode_ser_excluida(): void
    {
        $vidraria = vidraria::create(['nome' => 'Béquer', 'catmat' => 'VID-01', 'quantidade' => 3]);
        $this->postJson('/api/saidavidrarias', [
            'idvidraria' => $vidraria->idvidraria,
            'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 1,
            'motivo' => 'Quebra',
            'observacao' => 'Quebrou durante a lavagem',
        ])->assertCreated();

        $this->deleteJson("/api/vidrarias/{$vidraria->idvidraria}")->assertStatus(409);
        $this->assertNotNull($vidraria->fresh());
    }

    // ---------- Dashboard ----------

    public function test_dashboard_alerta_por_lote_conforme_meses_alerta(): void
    {
        $r = $this->reagente(['nome' => 'Com dois lotes', 'data_validade' => now()->addYears(2)->toDateString()]);
        $this->novoLote($r['idreagente'], 1, 'LOT-VENCE-LOGO', now()->addMonths(2)->toDateString());
        $this->reagente(['nome' => 'Vencido', 'data_validade' => now()->subDay()->toDateString()]);
        $this->reagente(['nome' => 'Fora do alerta', 'data_validade' => now()->addMonths(2)->toDateString(), 'meses_alerta' => 1]);

        $resposta = $this->getJson('/api/dashboard')->assertOk();

        $this->assertEquals(['Vencido'], array_column($resposta->json('alertas.vencidos'), 'nome'));
        $proximos = $resposta->json('alertas.proximos_vencimento');
        $this->assertEquals(['LOT-VENCE-LOGO'], array_column($proximos, 'lote'));
        $this->assertEquals(1, $proximos[0]['quantidade']);
    }
}
