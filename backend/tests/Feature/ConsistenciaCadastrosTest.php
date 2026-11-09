<?php

namespace Tests\Feature;

use App\Models\entrada_equipamento;
use App\Models\entrada_reagente;
use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\equipamento_patrimonio;
use App\Models\User;
use App\Models\unidade_medida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regras que protegem o valor real do estoque e a qualidade dos cadastros.
 */
class ConsistenciaCadastrosTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico']);
        $this->admin = User::factory()->create(['tipo' => 'admin']);
        Sanctum::actingAs($this->tecnico);
    }

    private function kg(): int
    {
        return unidade_medida::firstOrCreate(['sigla' => 'kg'], ['nome' => 'Quilograma'])->idunidademedida;
    }

    private function litro(): int
    {
        return unidade_medida::firstOrCreate(['sigla' => 'L'], ['nome' => 'Litro'])->idunidademedida;
    }

    private function reagente(array $extra = [])
    {
        return $this->postJson('/api/reagentes', array_merge([
            'nome' => 'Etanol', 'catmat' => 'R-1', 'idunidademedida' => $this->litro(),
            'idlaboratorio' => $this->labId(), 'quantidade' => 2, 'lote' => 'L1',
            'data_validade' => now()->addYear()->toDateString(),
        ], $extra));
    }

    // ---------- Unidade de medida ----------

    public function test_unidade_de_reagente_com_lote_nao_pode_ser_trocada(): void
    {
        $id = $this->reagente()->assertCreated()->json('idreagente');

        $this->putJson("/api/reagentes/{$id}", ['idunidademedida' => $this->kg()])->assertStatus(422);
        $this->getJson("/api/reagentes/{$id}")->assertJsonPath('idunidademedida', $this->litro());

        // Reenviar a mesma unidade (como a tela faz ao salvar) continua valendo
        $this->putJson("/api/reagentes/{$id}", ['idunidademedida' => $this->litro(), 'nome' => 'Etanol 96%'])->assertOk();
    }

    public function test_unidade_de_reagente_sem_lote_pode_ser_trocada(): void
    {
        $id = $this->reagente(['quantidade' => 0])->assertCreated()->json('idreagente');
        $this->putJson("/api/reagentes/{$id}", ['idunidademedida' => $this->kg()])->assertOk();
    }

    public function test_sigla_de_unidade_em_uso_nao_pode_ser_trocada(): void
    {
        $this->reagente()->assertCreated();
        $livre = unidade_medida::create(['nome' => 'Caixa', 'sigla' => 'cx']);

        Sanctum::actingAs($this->admin);
        $this->putJson('/api/unidademedidas/' . $this->litro(), ['sigla' => 'mL'])->assertStatus(422);
        $this->putJson('/api/unidademedidas/' . $this->litro(), ['nome' => 'Litros'])->assertOk();
        $this->putJson("/api/unidademedidas/{$livre->idunidademedida}", ['sigla' => 'pc'])->assertOk();
    }

    public function test_cadastros_de_apoio_so_o_admin_altera(): void
    {
        foreach (['unidademedidas', 'laboratorios', 'unidades'] as $recurso) {
            $this->getJson("/api/{$recurso}")->assertOk();
            $this->postJson("/api/{$recurso}", ['nome' => 'Novo', 'sigla' => 'nv'])->assertForbidden();
            $this->deleteJson("/api/{$recurso}/1")->assertForbidden();
        }

        Sanctum::actingAs($this->admin);
        $this->postJson('/api/laboratorios', ['nome' => 'Lab novo'])->assertCreated();
    }

    // ---------- Patrimônios antigos ----------

    public function test_estorno_nao_deixa_patrimonio_antigo_sem_unidade(): void
    {
        $lab = $this->labId();
        $e = equipamento::create(['nome' => 'Estufa', 'catmat' => 'E-1', 'quantidade' => 1]);
        equipamento_local::create(['idequipamento' => $e->idequipamento, 'idlaboratorio' => $lab, 'quantidade' => 1]);
        $entrada = entrada_equipamento::create([
            'idequipamento' => $e->idequipamento, 'idlaboratorio' => $lab, 'idusuario' => $this->tecnico->id,
            'quantidade' => 1, 'data' => now(),
        ]);
        // Cadastro antigo: o número existe, mas não está ligado à entrada
        equipamento_patrimonio::create(['idequipamento' => $e->idequipamento, 'numero' => 'ANT-1', 'idlaboratorio' => $lab]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/entradaequipamentos/{$entrada->identradaequipamento}")->assertStatus(422);
        $this->assertEquals(1, $e->fresh()->quantidade);
    }

    // ---------- Sessões ----------

    public function test_trocar_a_senha_derruba_os_logins_antigos(): void
    {
        $usuario = User::factory()->create(['tipo' => 'tecnico', 'password' => 'Antiga123']);
        $usuario->createToken('antigo');
        $this->assertEquals(1, $usuario->tokens()->count());

        Sanctum::actingAs($this->admin);
        $this->putJson("/api/usuarios/{$usuario->id}", ['nome' => 'Mesmo nome'])->assertOk();
        $this->assertEquals(1, $usuario->tokens()->count());

        $this->putJson("/api/usuarios/{$usuario->id}", ['password' => 'Trocada123'])->assertOk();
        $this->assertEquals(0, $usuario->tokens()->count());
    }

    public function test_quem_troca_a_propria_senha_continua_logado(): void
    {
        $token = $this->admin->createToken('atual')->plainTextToken;
        $this->admin->createToken('outro');
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->putJson("/api/usuarios/{$this->admin->id}", ['password' => 'Trocada123'])->assertOk();

        $this->assertEquals(['atual'], $this->admin->tokens()->pluck('name')->all());
    }

    public function test_login_apaga_tokens_vencidos_do_usuario(): void
    {
        $usuario = User::factory()->create(['tipo' => 'tecnico', 'password' => Hash::make('Senha1234')]);
        $usuario->createToken('vencido', ['*'], now()->subDay());
        $usuario->createToken('valido', ['*'], now()->addDay());

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'Senha1234'])->assertOk();

        $this->assertEqualsCanonicalizing(['valido', 'labstock-spa'], $usuario->tokens()->pluck('name')->all());
    }

    // ---------- Limites de quantidade ----------

    public function test_quantidade_gigante_recebe_mensagem_de_validacao(): void
    {
        $lab = $this->labId();
        $e = equipamento::create(['nome' => 'Estufa', 'catmat' => 'E-1', 'quantidade' => 0]);
        $v = $this->postJson('/api/vidrarias', ['nome' => 'Béquer', 'catmat' => 'V-1', 'quantidade' => 1])->assertCreated()->json();

        $this->postJson('/api/vidrarias', ['nome' => 'Proveta', 'catmat' => 'V-2', 'quantidade' => 99999999999])
            ->assertStatus(422)->assertJsonValidationErrors(['quantidade']);
        $this->postJson('/api/entradavidrarias', ['idvidraria' => $v['idvidraria'], 'idlaboratorio' => $lab, 'quantidade' => 99999999999])
            ->assertStatus(422)->assertJsonValidationErrors(['quantidade']);
        $this->postJson('/api/entradaequipamentos', ['idequipamento' => $e->idequipamento, 'idlaboratorio' => $lab, 'quantidade' => 99999999999])
            ->assertStatus(422)->assertJsonValidationErrors(['quantidade']);
    }

    public function test_quantidade_menor_que_1_mg_e_recusada(): void
    {
        $this->reagente(['quantidade' => 0.0000001])->assertStatus(422)->assertJsonValidationErrors(['quantidade']);

        $r = $this->reagente()->assertCreated()->json();
        $base = ['idreagente' => $r['idreagente'], 'quantidade' => 0.0000001];

        $this->postJson('/api/entradareagentes', $base + [
            'idlaboratorio' => $this->labId(), 'lote' => 'L2', 'data_validade' => now()->addYear()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['quantidade']);
        $this->postJson('/api/saidareagentes', $base + ['identrada' => $r['lotes'][0]['identradareagente']])
            ->assertStatus(422)->assertJsonValidationErrors(['quantidade']);

        // 1 mg é o mínimo aceito
        $this->postJson('/api/saidareagentes', ['idreagente' => $r['idreagente'], 'quantidade' => 0.000001, 'identrada' => $r['lotes'][0]['identradareagente']])
            ->assertCreated();
    }

    // ---------- Cadastros repetidos ----------

    public function test_nome_e_catmat_nao_se_repetem(): void
    {
        $id = $this->reagente()->assertCreated()->json('idreagente');

        $this->reagente(['catmat' => 'R-2'])->assertStatus(422)->assertJsonValidationErrors(['nome']);
        $this->reagente(['nome' => 'Metanol'])->assertStatus(422)->assertJsonValidationErrors(['catmat']);

        // Salvar o próprio cadastro sem mudar nada continua funcionando
        $this->putJson("/api/reagentes/{$id}", ['nome' => 'Etanol', 'catmat' => 'R-1'])->assertOk();

        $this->postJson('/api/vidrarias', ['nome' => 'Béquer', 'catmat' => 'V-1'])->assertCreated();
        $this->postJson('/api/vidrarias', ['nome' => 'Béquer', 'catmat' => 'V-2'])->assertStatus(422)->assertJsonValidationErrors(['nome']);
        $this->postJson('/api/vidrarias', ['nome' => 'Funil', 'catmat' => 'V-1'])->assertStatus(422)->assertJsonValidationErrors(['catmat']);

        $eq = $this->postJson('/api/equipamentos', ['nome' => 'Estufa', 'catmat' => 'E-1', 'quantidade' => 1])->assertCreated()->json('idequipamento');
        $this->postJson('/api/equipamentos', ['nome' => 'Estufa', 'catmat' => 'E-2'])->assertStatus(422)->assertJsonValidationErrors(['nome']);
        $this->postJson('/api/equipamentos', ['nome' => 'pHmetro', 'catmat' => 'E-1'])->assertStatus(422)->assertJsonValidationErrors(['catmat']);
        $this->putJson("/api/equipamentos/{$eq}", ['nome' => 'Estufa', 'catmat' => 'E-1'])->assertOk();
    }

    // ---------- Campos que não podem ficar em branco ----------

    public function test_produto_controlado_exige_classificacao(): void
    {
        $this->reagente(['controlado' => true])->assertStatus(422)->assertJsonValidationErrors(['classificacao_controle']);

        $id = $this->reagente(['controlado' => true, 'classificacao_controle' => 'Lista II - 032'])->assertCreated()->json('idreagente');
        $this->putJson("/api/reagentes/{$id}", ['classificacao_controle' => ''])->assertStatus(422)->assertJsonValidationErrors(['classificacao_controle']);
    }

    public function test_lote_precisa_de_laboratorio(): void
    {
        $this->reagente(['idlaboratorio' => null])->assertStatus(422)->assertJsonValidationErrors(['idlaboratorio']);

        $r = $this->reagente()->assertCreated()->json();
        $this->assertEquals($this->labId(), entrada_reagente::where('idreagente', $r['idreagente'])->value('idlaboratorio'));

        $this->postJson('/api/entradareagentes', [
            'idreagente' => $r['idreagente'], 'quantidade' => 1, 'lote' => 'L2', 'data_validade' => now()->addYear()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['idlaboratorio']);
    }

    public function test_cadastro_sem_estoque_nao_pede_dados_de_lote(): void
    {
        $this->postJson('/api/reagentes', [
            'nome' => 'Novo', 'catmat' => 'R-9', 'idunidademedida' => $this->litro(), 'quantidade' => 0,
        ])->assertCreated()->assertJsonPath('lotes', []);
    }
}
