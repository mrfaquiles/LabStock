<?php

namespace Tests\Feature;

use App\Models\entrada_vidraria;
use App\Models\historico_alteracao;
use App\Models\laboratorio;
use App\Models\unidade;
use App\Models\reagente;
use App\Models\unidade_medida;
use App\Models\User;
use App\Models\vidraria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * O estoque de vidraria segue a mesma regra de reagentes e equipamentos:
 * só muda por movimentação, e o cadastro fica no histórico.
 */
class EstoqueVidrariaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));
    }

    private function cadastrar(int $quantidade = 10): array
    {
        return $this->postJson('/api/vidrarias', [
            'nome' => 'Béquer 250 ml', 'catmat' => 'VID-01', 'quantidade' => $quantidade,
        ])->assertCreated()->json();
    }

    public function test_quantidade_do_cadastro_vira_entrada_inicial(): void
    {
        $v = $this->cadastrar(10);

        $entrada = entrada_vidraria::where('idvidraria', $v['idvidraria'])->sole();
        $this->assertEquals(10, $entrada->quantidade);
        $this->assertEquals('Estoque inicial (cadastro)', $entrada->observacao);
    }

    public function test_cadastro_sem_quantidade_nao_cria_entrada(): void
    {
        $v = $this->cadastrar(0);
        $this->assertFalse(entrada_vidraria::where('idvidraria', $v['idvidraria'])->exists());
    }

    public function test_edicao_nao_muda_o_estoque(): void
    {
        $v = $this->cadastrar(10);

        $this->putJson("/api/vidrarias/{$v['idvidraria']}", ['nome' => 'Béquer 250 ml (forma baixa)', 'quantidade' => 50])
            ->assertOk()->assertJsonPath('quantidade', 10)->assertJsonPath('nome', 'Béquer 250 ml (forma baixa)');
    }

    public function test_edicao_da_vidraria_fica_no_historico(): void
    {
        $v = $this->cadastrar(10);
        $this->putJson("/api/vidrarias/{$v['idvidraria']}", ['nome' => 'Béquer 600 ml'])->assertOk();

        $h = historico_alteracao::where('entidade', 'vidrarias')->where('acao', 'alterado')->sole();
        $this->assertEquals(['antes' => 'Béquer 250 ml', 'depois' => 'Béquer 600 ml'], $h->alteracoes['nome']);
    }

    public function test_cadastro_por_engano_pode_ser_excluido(): void
    {
        $v = $this->cadastrar(10);
        $this->deleteJson("/api/vidrarias/{$v['idvidraria']}")->assertOk();
        $this->assertNull(vidraria::find($v['idvidraria']));
    }

    public function test_vidraria_com_outra_entrada_nao_pode_ser_excluida(): void
    {
        $v = $this->cadastrar(10);
        $lab = laboratorio::create(['nome' => 'Lab. de Química', 'idunidade' => unidade::create(['nome' => 'Campus Muriaé'])->idunidade]);
        $this->postJson('/api/entradavidrarias', [
            'idvidraria' => $v['idvidraria'], 'idlaboratorio' => $lab->idlaboratorio, 'quantidade' => 5,
        ])->assertCreated();

        $this->deleteJson("/api/vidrarias/{$v['idvidraria']}")->assertStatus(409);
    }

    public function test_marcar_reagente_como_controlado_fica_no_historico(): void
    {
        $r = reagente::create([
            'nome' => 'Ácido sulfúrico', 'idunidademedida' => unidade_medida::create(['nome' => 'Litro', 'sigla' => 'L'])->idunidademedida,
        ]);

        $r->update(['controlado' => true, 'classificacao_controle' => 'Lista I']);

        $h = historico_alteracao::where('entidade', 'reagentes')->where('acao', 'alterado')->sole();
        $this->assertArrayHasKey('controlado', $h->alteracoes);
        $this->assertArrayHasKey('classificacao_controle', $h->alteracoes);
    }
}
