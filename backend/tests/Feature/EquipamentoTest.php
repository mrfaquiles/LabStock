<?php

namespace Tests\Feature;

use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\laboratorio;
use App\Models\unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EquipamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;
    private laboratorio $labUnidade1;
    private laboratorio $labUnidade2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico', 'nome' => 'Técnica Ana']);
        Sanctum::actingAs($this->tecnico);

        $u1 = unidade::create(['nome' => 'Unidade 1']);
        $u2 = unidade::create(['nome' => 'Unidade 2']);
        $this->labUnidade1 = laboratorio::create(['nome' => 'Lab. de Química', 'idunidade' => $u1->idunidade]);
        $this->labUnidade2 = laboratorio::create(['nome' => 'Lab. de Biologia', 'idunidade' => $u2->idunidade]);
    }

    private function microscopios(int $quantidade = 2): array
    {
        return $this->postJson('/api/equipamentos', [
            'nome' => 'Microscópio', 'catmat' => '123', 'patrimonio' => 'PAT-1',
            'quantidade' => $quantidade, 'idlaboratorio' => $this->labUnidade1->idlaboratorio,
        ])->assertCreated()->json();
    }

    private function quantidadeEm(int $idequipamento, ?laboratorio $lab): int
    {
        return (int) equipamento_local::where('idequipamento', $idequipamento)
            ->where('idlaboratorio', $lab?->idlaboratorio)->value('quantidade');
    }

    public function test_cadastro_coloca_a_quantidade_no_laboratorio_escolhido(): void
    {
        $m = $this->microscopios(2);

        $this->assertEquals(2, $m['quantidade']);
        $this->assertEquals('Lab. de Química', $m['locais'][0]['laboratorio']['nome']);
        $this->assertEquals('Unidade 1', $m['locais'][0]['laboratorio']['unidade']['nome']);
    }

    public function test_transfere_um_microscopio_da_unidade_1_para_a_2(): void
    {
        $m = $this->microscopios(2);

        $this->postJson("/api/equipamentos/{$m['idequipamento']}/transferir", [
            'idorigem' => $this->labUnidade1->idlaboratorio,
            'iddestino' => $this->labUnidade2->idlaboratorio,
            'quantidade' => 1,
            'observacao' => 'Aula de microbiologia na Unidade 2',
        ])->assertCreated();

        $this->assertEquals(1, $this->quantidadeEm($m['idequipamento'], $this->labUnidade1));
        $this->assertEquals(1, $this->quantidadeEm($m['idequipamento'], $this->labUnidade2));
        // O total não muda numa transferência
        $this->assertEquals(2, equipamento::find($m['idequipamento'])->quantidade);
    }

    public function test_nao_transfere_mais_do_que_existe_na_origem(): void
    {
        $m = $this->microscopios(1);

        $this->postJson("/api/equipamentos/{$m['idequipamento']}/transferir", [
            'idorigem' => $this->labUnidade1->idlaboratorio,
            'iddestino' => $this->labUnidade2->idlaboratorio,
            'quantidade' => 2,
        ])->assertStatus(422);
    }

    public function test_origem_e_destino_precisam_ser_diferentes(): void
    {
        $m = $this->microscopios(1);

        $this->postJson("/api/equipamentos/{$m['idequipamento']}/transferir", [
            'idorigem' => $this->labUnidade1->idlaboratorio,
            'iddestino' => $this->labUnidade1->idlaboratorio,
            'quantidade' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['iddestino']);
    }

    public function test_equipamento_sem_local_pode_ser_transferido_para_um_laboratorio(): void
    {
        $id = $this->postJson('/api/equipamentos', ['nome' => 'Centrífuga', 'catmat' => '9', 'quantidade' => 1])
            ->json('idequipamento');

        $this->postJson("/api/equipamentos/{$id}/transferir", [
            'idorigem' => null, 'iddestino' => $this->labUnidade2->idlaboratorio, 'quantidade' => 1,
        ])->assertCreated();

        $this->assertEquals(0, $this->quantidadeEm($id, null));
        $this->assertEquals(1, $this->quantidadeEm($id, $this->labUnidade2));
    }

    public function test_baixa_exige_motivo_e_sai_do_laboratorio(): void
    {
        $m = $this->microscopios(2);

        $this->postJson('/api/saidaequipamentos', [
            'idequipamento' => $m['idequipamento'], 'idlaboratorio' => $this->labUnidade1->idlaboratorio, 'quantidade' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['observacao']);

        $this->postJson('/api/saidaequipamentos', [
            'idequipamento' => $m['idequipamento'], 'idlaboratorio' => $this->labUnidade1->idlaboratorio,
            'quantidade' => 1, 'observacao' => 'Lente quebrada sem conserto',
        ])->assertCreated();

        $this->assertEquals(1, $this->quantidadeEm($m['idequipamento'], $this->labUnidade1));
        $this->assertEquals(1, equipamento::find($m['idequipamento'])->quantidade);
    }

    public function test_baixa_recusada_se_nao_ha_unidades_no_local(): void
    {
        $m = $this->microscopios(1);

        $this->postJson('/api/saidaequipamentos', [
            'idequipamento' => $m['idequipamento'], 'idlaboratorio' => $this->labUnidade2->idlaboratorio,
            'quantidade' => 1, 'observacao' => 'Defeito no motor',
        ])->assertStatus(422);
    }

    public function test_edicao_fica_no_historico_com_antes_e_depois(): void
    {
        $m = $this->microscopios(1);

        $this->putJson("/api/equipamentos/{$m['idequipamento']}", ['status' => 'Em Manutenção', 'patrimonio' => 'PAT-1'])
            ->assertOk();

        $historico = $this->getJson("/api/equipamentos/{$m['idequipamento']}/historico")->assertOk()->json();

        $edicao = collect($historico)->firstWhere('tipo', 'alterado');
        $this->assertEquals('Técnica Ana', $edicao['usuario']);
        $this->assertEquals(['antes' => 'Operacional', 'depois' => 'Em Manutenção'], $edicao['alteracoes']['status']);
        // Campo que não mudou não aparece
        $this->assertArrayNotHasKey('patrimonio', $edicao['alteracoes']);
        $this->assertNotNull(collect($historico)->firstWhere('tipo', 'criado'));
    }

    public function test_historico_mostra_transferencia_com_unidades(): void
    {
        $m = $this->microscopios(2);
        $this->postJson("/api/equipamentos/{$m['idequipamento']}/transferir", [
            'idorigem' => $this->labUnidade1->idlaboratorio, 'iddestino' => $this->labUnidade2->idlaboratorio, 'quantidade' => 1,
        ])->assertCreated();

        $transferencia = collect($this->getJson("/api/equipamentos/{$m['idequipamento']}/historico")->json())
            ->firstWhere('tipo', 'transferencia');

        $this->assertEquals('Unidade 1 › Lab. de Química', $transferencia['origem']);
        $this->assertEquals('Unidade 2 › Lab. de Biologia', $transferencia['destino']);
    }

    public function test_edicao_nao_altera_quantidade(): void
    {
        $m = $this->microscopios(2);

        $this->putJson("/api/equipamentos/{$m['idequipamento']}", ['quantidade' => 10])->assertOk();
        $this->assertEquals(2, equipamento::find($m['idequipamento'])->quantidade);
    }

    public function test_consulta_ve_historico_mas_nao_transfere(): void
    {
        $m = $this->microscopios(1);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'consulta']));

        $this->getJson("/api/equipamentos/{$m['idequipamento']}/historico")->assertOk();
        $this->postJson("/api/equipamentos/{$m['idequipamento']}/transferir", [
            'idorigem' => $this->labUnidade1->idlaboratorio, 'iddestino' => $this->labUnidade2->idlaboratorio, 'quantidade' => 1,
        ])->assertForbidden();
    }
}
