<?php

namespace Tests\Feature;

use App\Models\entrada_reagente;
use App\Models\historico_alteracao;
use App\Models\User;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Data das movimentações, uso de lote vencido, estorno só pelo administrador
 * (com rastro no histórico) e origem opcional do lote inicial.
 */
class RegrasMovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnidadeMedidaSeeder::class);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));
    }

    private function reagente(array $extra = []): array
    {
        return $this->postJson('/api/reagentes', array_merge([
            'nome' => 'Ácido Acético', 'catmat' => '10' . ($extra['nome'] ?? ''), 'idunidademedida' => 2, 'idlaboratorio' => $this->labId(),
            'quantidade' => 5, 'lote' => 'AC-01', 'data_validade' => now()->addYear()->toDateString(),
        ], $extra))->assertCreated()->json();
    }

    private function usar(array $r, array $extra = [])
    {
        return $this->postJson('/api/saidareagentes', array_merge([
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 0.5,
        ], $extra));
    }

    // ---------- Data ----------

    public function test_movimentacao_com_data_futura_e_recusada(): void
    {
        $r = $this->reagente();

        $this->usar($r, ['data' => now()->addDay()->toDateString()])
            ->assertStatus(422)->assertJsonPath('errors.data.0', 'A data da movimentação não pode ser futura.');

        $this->postJson('/api/entradareagentes', [
            'idreagente' => $r['idreagente'], 'idlaboratorio' => $this->labId(), 'quantidade' => 1, 'lote' => 'AC-02', 'observacao' => 'NF 1',
            'data_validade' => now()->addYear()->toDateString(), 'data' => now()->addMonth()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['data']);

        $this->usar($r, ['data' => now()->toDateString()])->assertCreated();
    }

    public function test_uso_nao_pode_ser_anterior_a_entrada_do_lote(): void
    {
        $r = $this->reagente();

        $this->usar($r, ['data' => now()->subMonth()->toDateString()])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'não pode ser anterior à entrada do lote AC-01'));
    }

    // ---------- Lote vencido ----------

    public function test_uso_de_lote_vencido_exige_confirmacao(): void
    {
        $r = $this->reagente();
        entrada_reagente::where('idreagente', $r['idreagente'])->update(['data_validade' => now()->subDay()->toDateString()]);

        $this->usar($r)->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'está vencido'));

        $this->usar($r, ['confirmar_vencido' => true])->assertCreated();
    }

    public function test_lote_dentro_da_validade_nao_pede_confirmacao(): void
    {
        $this->usar($this->reagente())->assertCreated();
    }

    // ---------- Estorno ----------

    public function test_tecnico_nao_estorna_e_admin_estorna_deixando_rastro(): void
    {
        $r = $this->reagente();
        $id = $this->usar($r, ['observacao' => 'Aula 1'])->json('idsaidareagente');

        $this->deleteJson("/api/saidareagentes/{$id}")->assertForbidden();

        $admin = User::factory()->create(['tipo' => 'admin']);
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/saidareagentes/{$id}")->assertOk();

        $rastro = historico_alteracao::where('entidade', 'saida_reagentes')
            ->where('registro_id', $id)->where('acao', 'excluido')->first();

        $this->assertNotNull($rastro);
        $this->assertEquals($admin->id, $rastro->idusuario);
        $this->assertEquals(0.5, (float) $rastro->alteracoes['quantidade']['antes']);
        $this->assertEquals('Aula 1', $rastro->alteracoes['observacao']['antes']);
    }

    public function test_edicao_pelo_admin_fica_no_historico(): void
    {
        $id = $this->usar($this->reagente(), ['observacao' => 'Antes'])->json('idsaidareagente');

        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->putJson("/api/saidareagentes/{$id}", ['observacao' => 'Depois'])->assertOk();

        $rastro = historico_alteracao::where('entidade', 'saida_reagentes')->where('acao', 'alterado')->first();
        $this->assertEquals(['antes' => 'Antes', 'depois' => 'Depois'], $rastro->alteracoes['observacao']);
    }

    public function test_registrar_movimentacao_nao_duplica_no_historico(): void
    {
        $this->usar($this->reagente())->assertCreated();
        // O uso já é o próprio registro (o cadastro do reagente, sim, entra no histórico)
        $this->assertEquals(0, historico_alteracao::where('entidade', 'saida_reagentes')->count());
    }

    // ---------- Origem do lote inicial ----------

    public function test_origem_do_lote_inicial_e_opcional(): void
    {
        $com = $this->reagente(['origem' => 'Nota fiscal 555']);
        $sem = $this->reagente(['nome' => 'Etanol']);

        $this->assertEquals('Nota fiscal 555', entrada_reagente::where('idreagente', $com['idreagente'])->value('observacao'));
        $this->assertEquals('Lote inicial (cadastro do reagente)', entrada_reagente::where('idreagente', $sem['idreagente'])->value('observacao'));
    }
}
