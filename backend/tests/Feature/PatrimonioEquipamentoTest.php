<?php

namespace Tests\Feature;

use App\Models\entrada_equipamento;
use App\Models\equipamento;
use App\Models\equipamento_patrimonio;
use App\Models\laboratorio;
use App\Models\unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cada unidade de equipamento tem o seu número de patrimônio:
 * cadastrar um número soma 1 ao estoque e dar baixa nele tira 1.
 */
class PatrimonioEquipamentoTest extends TestCase
{
    use RefreshDatabase;

    private laboratorio $lab;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));
        $u = unidade::create(['nome' => 'Campus Barra']);
        $this->lab = laboratorio::create(['nome' => 'Lab. de Química', 'idunidade' => $u->idunidade]);
    }

    private function cadastrar(array $patrimonios, array $extra = [])
    {
        return $this->postJson('/api/equipamentos', array_merge([
            'nome' => 'Balança analítica', 'catmat' => '150420', 'quantidade' => 99,
            'idlaboratorio' => $this->lab->idlaboratorio, 'patrimonios' => $patrimonios,
        ], $extra));
    }

    private function estoque(int $id): int
    {
        return (int) equipamento::find($id)->quantidade;
    }

    public function test_cada_patrimonio_do_cadastro_vira_uma_unidade(): void
    {
        $e = $this->cadastrar(['IF-001', 'IF-002', 'IF-003'])->assertCreated()->json();

        $this->assertEquals(3, $e['quantidade']); // ignora o "99" e conta os números
        $this->assertEqualsCanonicalizing(['IF-001', 'IF-002', 'IF-003'], array_column($e['patrimonios'], 'numero'));
        $this->assertEquals(3, $e['locais'][0]['quantidade']);
    }

    public function test_sem_patrimonio_continua_usando_a_quantidade(): void
    {
        $e = $this->cadastrar([], ['quantidade' => 2])->assertCreated()->json();
        $this->assertEquals(2, $e['quantidade']);
        $this->assertEmpty($e['patrimonios']);
    }

    public function test_patrimonio_repetido_e_recusado(): void
    {
        $this->cadastrar(['IF-001'])->assertCreated();

        $this->cadastrar(['IF-001'])->assertStatus(422)
            ->assertJsonPath('errors', fn ($e) => str_contains(json_encode($e, JSON_UNESCAPED_UNICODE), 'IF-001 já está cadastrado'));
        $this->cadastrar(['IF-009', 'if-009'])->assertStatus(422);
    }

    public function test_adicionar_patrimonio_na_entrada_soma_ao_estoque(): void
    {
        $id = $this->cadastrar(['IF-001'])->json('idequipamento');

        $this->postJson('/api/entradaequipamentos', [
            'idequipamento' => $id, 'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 2, 'patrimonios' => ['IF-002', 'IF-003'],
        ])->assertCreated()->assertJsonPath('observacao', 'Patrimônio(s): IF-002, IF-003');

        $this->assertEquals(3, $this->estoque($id));
        $this->assertEquals(3, equipamento_patrimonio::ativos()->where('idequipamento', $id)->count());
    }

    public function test_quantidade_diferente_dos_patrimonios_e_recusada(): void
    {
        $id = $this->cadastrar(['IF-001'])->json('idequipamento');

        $this->postJson('/api/entradaequipamentos', [
            'idequipamento' => $id, 'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 3, 'patrimonios' => ['IF-002'],
        ])->assertStatus(422);
    }

    public function test_baixa_de_um_patrimonio_tira_a_unidade_e_guarda_o_numero(): void
    {
        $e = $this->cadastrar(['IF-001', 'IF-002'])->json();
        $alvo = collect($e['patrimonios'])->firstWhere('numero', 'IF-002')['idpatrimonio'];

        $this->postJson('/api/saidaequipamentos', [
            'idequipamento' => $e['idequipamento'], 'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 1, 'observacao' => 'Defeito sem conserto', 'patrimonios_baixa' => [$alvo],
        ])->assertCreated();

        $this->assertEquals(1, $this->estoque($e['idequipamento']));
        $this->assertNotNull(equipamento_patrimonio::find($alvo)->baixado_em);
        $this->assertEquals(['IF-001'], array_column(
            $this->getJson("/api/equipamentos/{$e['idequipamento']}")->json('patrimonios'), 'numero'
        ));
    }

    public function test_baixa_sem_dizer_qual_patrimonio_e_recusada_quando_todos_tem_numero(): void
    {
        $e = $this->cadastrar(['IF-001', 'IF-002'])->json();

        $this->postJson('/api/saidaequipamentos', [
            'idequipamento' => $e['idequipamento'], 'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 1, 'observacao' => 'Defeito sem conserto',
        ])->assertStatus(422)->assertJsonPath('message', 'Escolha quais patrimônios estão saindo deste local.');
    }

    // ---------- Transferência ----------

    private function outroLab(): laboratorio
    {
        $u = unidade::create(['nome' => 'Campus Sofocó']);
        return laboratorio::create(['nome' => 'Lab. de Solos', 'idunidade' => $u->idunidade]);
    }

    public function test_transferencia_leva_o_patrimonio_escolhido_para_o_destino(): void
    {
        $destino = $this->outroLab();
        $e = $this->cadastrar(['IF-001', 'IF-002', 'IF-003'])->json();
        $alvo = collect($e['patrimonios'])->firstWhere('numero', 'IF-002');

        $this->postJson("/api/equipamentos/{$e['idequipamento']}/transferir", [
            'idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio,
            'quantidade' => 1, 'patrimonios' => [$alvo['idpatrimonio']],
        ])->assertCreated()->assertJsonPath('transferencia.observacao', 'Patrimônio(s): IF-002');

        $this->assertEquals($destino->idlaboratorio, equipamento_patrimonio::find($alvo['idpatrimonio'])->idlaboratorio);
        $this->assertEquals(2, equipamento_patrimonio::where('idlaboratorio', $this->lab->idlaboratorio)->count());
    }

    public function test_transferencia_sem_escolher_patrimonio_e_recusada(): void
    {
        $destino = $this->outroLab();
        $e = $this->cadastrar(['IF-001', 'IF-002'])->json();

        $this->postJson("/api/equipamentos/{$e['idequipamento']}/transferir", [
            'idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio, 'quantidade' => 1,
        ])->assertStatus(422)->assertJsonPath('message', 'Escolha quais patrimônios estão saindo deste local.');
    }

    public function test_nao_transfere_patrimonio_que_esta_em_outro_local(): void
    {
        $destino = $this->outroLab();
        $e = $this->cadastrar(['IF-001', 'IF-002'])->json();
        $id = $e['patrimonios'][0]['idpatrimonio'];
        $url = "/api/equipamentos/{$e['idequipamento']}/transferir";

        $this->postJson($url, ['idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio, 'quantidade' => 1, 'patrimonios' => [$id]])->assertCreated();

        // O mesmo patrimônio já não está mais na origem
        $this->postJson($url, ['idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio, 'quantidade' => 1, 'patrimonios' => [$id]])
            ->assertStatus(422)->assertJsonPath('message', 'Um dos patrimônios escolhidos não está neste local ou já recebeu baixa.');
    }

    public function test_unidades_sem_patrimonio_transferem_sem_escolher(): void
    {
        $destino = $this->outroLab();
        $id = $this->cadastrar([], ['quantidade' => 3])->json('idequipamento');

        $this->postJson("/api/equipamentos/{$id}/transferir", [
            'idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio, 'quantidade' => 2,
        ])->assertCreated();
    }

    public function test_o_cadastro_mostra_onde_esta_cada_patrimonio(): void
    {
        $e = $this->cadastrar(['IF-001'])->json();
        $this->assertEquals('Lab. de Química', $e['patrimonios'][0]['laboratorio']['nome']);
    }

    public function test_estorno_da_entrada_remove_os_patrimonios(): void
    {
        $id = $this->cadastrar(['IF-001'])->json('idequipamento');
        $entrada = $this->postJson('/api/entradaequipamentos', [
            'idequipamento' => $id, 'idlaboratorio' => $this->lab->idlaboratorio,
            'quantidade' => 1, 'patrimonios' => ['IF-002'],
        ])->json('identradaequipamento');

        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->deleteJson("/api/entradaequipamentos/{$entrada}")->assertOk();

        $this->assertEquals(1, $this->estoque($id));
        $this->assertNull(equipamento_patrimonio::where('numero', 'IF-002')->first());
    }
    // ---------- Estoque inicial ----------

    public function test_cadastro_gera_entrada_inicial_ligada_aos_patrimonios(): void
    {
        $e = $this->cadastrar(['IF-001', 'IF-002'])->json();

        $entrada = entrada_equipamento::where('idequipamento', $e['idequipamento'])->sole();
        $this->assertEquals(2, $entrada->quantidade);
        $this->assertEquals('Estoque inicial (cadastro) — Patrimônio(s): IF-001, IF-002', $entrada->observacao);
        $this->assertEquals(2, equipamento_patrimonio::where('identrada', $entrada->identradaequipamento)->count());
    }

    public function test_cadastro_por_engano_pode_ser_excluido(): void
    {
        $id = $this->cadastrar(['IF-001'])->json('idequipamento');
        $this->deleteJson("/api/equipamentos/{$id}")->assertOk();
        $this->assertNull(equipamento::find($id));
    }

    public function test_equipamento_transferido_nao_pode_ser_excluido(): void
    {
        $destino = $this->outroLab();
        $id = $this->cadastrar([], ['quantidade' => 2])->json('idequipamento');
        $this->postJson("/api/equipamentos/{$id}/transferir", [
            'idorigem' => $this->lab->idlaboratorio, 'iddestino' => $destino->idlaboratorio, 'quantidade' => 1,
        ])->assertCreated();

        $this->deleteJson("/api/equipamentos/{$id}")->assertStatus(409);
    }
}
