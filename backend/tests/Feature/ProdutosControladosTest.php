<?php

namespace Tests\Feature;

use App\Models\saida_reagente;
use App\Models\User;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProdutosControladosTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;
    private array $amonia;
    private int $lote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnidadeMedidaSeeder::class);
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico', 'nome' => 'Técnica Ana']);
        Sanctum::actingAs($this->tecnico);

        $this->amonia = $this->reagente()->assertCreated()->json();
        $this->lote = $this->amonia['lotes'][0]['identradareagente'];
    }

    private function reagente(array $extra = [])
    {
        return $this->postJson('/api/reagentes', array_merge([
            'nome' => 'Hidróxido de Amônio 28%', 'catmat' => '100' . ($extra['nome'] ?? ''), 'idlaboratorio' => $this->labId(),
            'controlado' => true, 'classificacao_controle' => 'Lista V - 066',
            'idunidademedida' => 2, 'quantidade' => 5, 'lote' => 'AM-01',
            'data_validade' => now()->addYears(2)->toDateString(),
        ], $extra));
    }

    private function usar(array $extra = [])
    {
        return $this->postJson('/api/saidareagentes', array_merge([
            'idreagente' => $this->amonia['idreagente'], 'identrada' => $this->lote,
            'quantidade' => 0.25, 'observacao' => 'Aula prática de titulação - Turma B',
        ], $extra));
    }

    // ---------- Cadastro ----------

    public function test_reagente_controlado_e_cadastrado_com_a_classificacao(): void
    {
        $this->assertTrue($this->amonia['controlado']);
        $this->assertEquals('Lista V - 066', $this->amonia['classificacao_controle']);
    }

    // ---------- Código CAS (opcional) ----------

    public function test_cas_e_opcional(): void
    {
        $this->assertNull($this->amonia['cas']);
        $this->reagente(['nome' => 'Etanol', 'cas' => ''])->assertCreated()->assertJsonPath('cas', null);
    }

    public function test_cas_valido_fica_gravado_e_pode_ser_editado(): void
    {
        $this->reagente(['nome' => 'Etanol', 'cas' => '64-17-5'])->assertCreated()->assertJsonPath('cas', '64-17-5');

        $this->putJson("/api/reagentes/{$this->amonia['idreagente']}", ['cas' => '1336-21-6'])
            ->assertOk()->assertJsonPath('cas', '1336-21-6');
    }

    public function test_cas_com_formato_ou_digito_errado_e_recusado(): void
    {
        $this->reagente(['nome' => 'Etanol', 'cas' => 'abc'])->assertStatus(422)->assertJsonValidationErrors(['cas']);
        $this->reagente(['nome' => 'Etanol', 'cas' => '64-17-6'])->assertStatus(422)->assertJsonValidationErrors(['cas']);
    }

    // ---------- Justificativa ----------

    public function test_uso_de_controlado_exige_justificativa(): void
    {
        $this->usar(['observacao' => null])->assertStatus(422);
        $this->usar(['observacao' => 'aula'])->assertStatus(422);
        $this->usar()->assertCreated();
    }

    public function test_reagente_comum_nao_exige_justificativa(): void
    {
        $comum = $this->reagente(['nome' => 'Sal comum', 'controlado' => false])->json();

        $this->postJson('/api/saidareagentes', [
            'idreagente' => $comum['idreagente'], 'identrada' => $comum['lotes'][0]['identradareagente'], 'quantidade' => 0.1,
        ])->assertCreated();
    }

    public function test_entrada_de_controlado_exige_origem(): void
    {
        $dados = [
            'idreagente' => $this->amonia['idreagente'], 'idlaboratorio' => $this->labId(), 'quantidade' => 1, 'lote' => 'AM-02',
            'data_validade' => now()->addYear()->toDateString(),
        ];
        $this->postJson('/api/entradareagentes', $dados)->assertStatus(422);
        $this->postJson('/api/entradareagentes', $dados + ['observacao' => 'Nota fiscal 1234'])->assertCreated();
    }

    // ---------- Registro não pode ser adulterado ----------

    public function test_registro_de_uso_nao_pode_ser_apagado_nem_editado(): void
    {
        $id = $this->usar()->json('idsaidareagente');
        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));

        $this->deleteJson("/api/saidareagentes/{$id}")->assertStatus(422);
        $this->putJson("/api/saidareagentes/{$id}", ['observacao' => 'Texto trocado depois'])->assertStatus(422);

        $this->assertEquals('Aula prática de titulação - Turma B', saida_reagente::find($id)->observacao);
    }

    public function test_entrada_de_controlado_nao_pode_ser_apagada(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->deleteJson("/api/entradareagentes/{$this->lote}")->assertStatus(422);
    }

    public function test_lote_de_controlado_nao_pode_ser_corrigido(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));

        $this->putJson("/api/entradareagentes/{$this->lote}", ['lote' => 'OUTRO'])->assertStatus(422);
        $this->getJson("/api/reagentes/{$this->amonia['idreagente']}")->assertJsonPath('lotes.0.lote', 'AM-01');
    }

    public function test_so_admin_tira_produto_do_controle_ou_exclui(): void
    {
        $id = $this->amonia['idreagente'];

        $this->putJson("/api/reagentes/{$id}", ['controlado' => false])->assertForbidden();
        $this->deleteJson("/api/reagentes/{$id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->putJson("/api/reagentes/{$id}", ['controlado' => false])->assertOk()->assertJsonPath('controlado', false);
    }

    // ---------- Relatório ----------

    private function relatorio(string $extra = ''): array
    {
        $hoje = now()->toDateString();
        return $this->getJson("/api/relatorios/controlados?data_inicio={$hoje}&data_fim={$hoje}{$extra}")->assertOk()->json();
    }

    public function test_relatorio_mostra_quantidade_justificativa_usuario_data_e_lote(): void
    {
        $this->usar()->assertCreated();

        $uso = collect($this->relatorio()['movimentacoes'])->firstWhere('tipo', 'uso');

        $this->assertEquals(0.25, $uso['quantidade']);
        $this->assertEquals('L', $uso['unidade']);
        $this->assertEquals('Aula prática de titulação - Turma B', $uso['justificativa']);
        $this->assertEquals('Técnica Ana', $uso['usuario']);
        $this->assertEquals(now()->toDateString(), $uso['data']);
        $this->assertEquals('AM-01', $uso['lote']);
    }

    public function test_relatorio_calcula_saldo_do_periodo(): void
    {
        $this->usar()->assertCreated();

        $resumo = $this->relatorio()['resumo'][0];

        $this->assertEquals(0, $resumo['saldo_inicial']);
        $this->assertEquals(5, $resumo['entradas']);
        $this->assertEquals(0.25, $resumo['usos']);
        $this->assertEquals(4.75, $resumo['saldo_final']);
        $this->assertEquals('Lista V - 066', $resumo['classificacao_controle']);
    }

    public function test_relatorio_nao_inclui_reagente_comum_e_filtra_por_produto(): void
    {
        $this->reagente(['nome' => 'Sal de cozinha', 'controlado' => false])->assertCreated();
        $outro = $this->reagente(['nome' => 'Acetona'])->json('idreagente');

        $this->assertEqualsCanonicalizing(['Hidróxido de Amônio 28%', 'Acetona'], array_column($this->relatorio()['resumo'], 'nome'));
        $this->assertEquals(['Acetona'], array_column($this->relatorio("&idreagente={$outro}")['resumo'], 'nome'));
    }

    public function test_relatorio_conta_usos_antigos_sem_justificativa(): void
    {
        // Reagente usado antes de ser marcado como controlado
        $r = $this->reagente(['nome' => 'Tolueno', 'controlado' => false])->json();
        $this->postJson('/api/saidareagentes', [
            'idreagente' => $r['idreagente'], 'identrada' => $r['lotes'][0]['identradareagente'], 'quantidade' => 0.1,
        ])->assertCreated();
        $this->putJson("/api/reagentes/{$r['idreagente']}", ['controlado' => true, 'classificacao_controle' => 'Lista II - 038'])->assertOk();

        $resumo = collect($this->relatorio()['resumo'])->firstWhere('nome', 'Tolueno');
        $this->assertEquals(1, $resumo['usos_sem_justificativa']);
    }
}
