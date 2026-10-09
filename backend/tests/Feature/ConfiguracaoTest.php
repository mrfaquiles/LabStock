<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\vidraria;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfiguracaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['tipo' => 'admin', 'password' => 'senha-teste-123']);
    }

    public function test_configuracoes_vem_com_valores_padrao(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'consulta']));

        $this->getJson('/api/configuracoes')
            ->assertOk()
            ->assertJsonPath('meses_alerta_padrao', 4)
            ->assertJsonPath('vidraria_exige_aprovacao', true)
            ->assertJsonPath('sessao_horas', 8);
    }

    public function test_so_admin_altera_configuracoes(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));
        $this->putJson('/api/configuracoes', ['instituicao_nome' => 'IFX'])->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->putJson('/api/configuracoes', ['instituicao_nome' => 'IF Goiano - Campus Rio Verde'])
            ->assertOk()
            ->assertJsonPath('instituicao_nome', 'IF Goiano - Campus Rio Verde');

        $this->getJson('/api/configuracoes')->assertJsonPath('instituicao_nome', 'IF Goiano - Campus Rio Verde');
    }

    public function test_valores_invalidos_sao_recusados(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/configuracoes', ['meses_alerta_padrao' => 0, 'sessao_horas' => 500])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['meses_alerta_padrao', 'sessao_horas']);
    }

    public function test_alerta_padrao_e_usado_no_cadastro_de_reagente(): void
    {
        $this->seed(UnidadeMedidaSeeder::class);
        Sanctum::actingAs($this->admin);
        $this->putJson('/api/configuracoes', ['meses_alerta_padrao' => 6])->assertOk();

        $this->postJson('/api/reagentes', [
            'nome' => 'Etanol', 'catmat' => '1', 'idunidademedida' => 2, 'quantidade' => 1,
            'lote' => 'L1', 'data_validade' => now()->addYear()->toDateString(),
        ])->assertCreated()->assertJsonPath('meses_alerta', 6);
    }

    public function test_baixa_de_vidraria_sem_aprovacao_quando_desligada(): void
    {
        Sanctum::actingAs($this->admin);
        $this->putJson('/api/configuracoes', ['vidraria_exige_aprovacao' => false])->assertOk();

        $bequer = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 5]);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));

        $this->postJson('/api/saidavidrarias', [
            'idvidraria' => $bequer->idvidraria, 'quantidade' => 1, 'motivo' => 'Quebra',
            'observacao' => 'Caiu da bancada durante a aula',
        ])->assertCreated()->assertJsonPath('status', 'aprovada');

        $this->assertEquals(4, $bequer->fresh()->quantidade);
    }

    public function test_login_usa_duracao_de_sessao_configurada(): void
    {
        Sanctum::actingAs($this->admin);
        $this->putJson('/api/configuracoes', ['sessao_horas' => 2])->assertOk();

        $this->postJson('/api/login', ['email' => $this->admin->email, 'password' => 'senha-teste-123'])->assertOk();

        $token = $this->admin->tokens()->latest('id')->first();
        $this->assertEqualsWithDelta(now()->addHours(2)->timestamp, $token->expires_at->timestamp, 5);
    }
}
