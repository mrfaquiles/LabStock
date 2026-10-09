<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\vidraria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SolicitacaoBaixaVidrariaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tecnico;
    private vidraria $bequer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['tipo' => 'admin']);
        $this->tecnico = User::factory()->create(['tipo' => 'tecnico']);
        $this->bequer = vidraria::create(['nome' => 'Béquer 250 ml', 'catmat' => 'VID-01', 'quantidade' => 10]);
    }

    private function solicitar(array $extra = [])
    {
        return $this->postJson('/api/saidavidrarias', array_merge([
            'idvidraria' => $this->bequer->idvidraria,
            'quantidade' => 2,
            'motivo' => 'Quebra',
            'observacao' => 'Béquer caiu da bancada durante a aula prática',
        ], $extra));
    }

    public function test_usuario_solicita_e_estoque_nao_muda_ate_aprovacao(): void
    {
        Sanctum::actingAs($this->tecnico);

        $this->solicitar()
            ->assertCreated()
            ->assertJsonPath('status', 'pendente')
            ->assertJsonPath('usuario.id', $this->tecnico->id);

        $this->assertEquals(10, $this->bequer->fresh()->quantidade);
    }

    public function test_perfil_consulta_tambem_pode_solicitar(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'consulta']));

        $this->solicitar()->assertCreated()->assertJsonPath('status', 'pendente');
    }

    public function test_justificativa_e_motivo_sao_obrigatorios(): void
    {
        Sanctum::actingAs($this->tecnico);

        $this->solicitar(['observacao' => '', 'motivo' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['observacao', 'motivo'])
            ->assertJsonPath('errors.observacao.0', 'A justificativa da baixa é obrigatória.');

        $this->solicitar(['observacao' => 'quebrou'])
            ->assertStatus(422)
            ->assertJsonPath('errors.observacao.0', 'Descreva a justificativa com pelo menos 10 caracteres.');
    }

    public function test_admin_aprova_e_estoque_diminui(): void
    {
        Sanctum::actingAs($this->tecnico);
        $id = $this->solicitar()->json('idsaidavidraria');

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/saidavidrarias/{$id}/aprovar")
            ->assertOk()
            ->assertJsonPath('status', 'aprovada')
            ->assertJsonPath('aprovador.id', $this->admin->id);

        $this->assertEquals(8, $this->bequer->fresh()->quantidade);
    }

    public function test_admin_recusa_com_motivo_e_estoque_nao_muda(): void
    {
        Sanctum::actingAs($this->tecnico);
        $id = $this->solicitar()->json('idsaidavidraria');

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/saidavidrarias/{$id}/recusar")
            ->assertStatus(422)->assertJsonValidationErrors(['motivo_recusa']);

        $this->postJson("/api/saidavidrarias/{$id}/recusar", ['motivo_recusa' => 'O béquer foi encontrado inteiro no armário'])
            ->assertOk()
            ->assertJsonPath('status', 'recusada');

        $this->assertEquals(10, $this->bequer->fresh()->quantidade);
    }

    public function test_tecnico_nao_pode_aprovar(): void
    {
        Sanctum::actingAs($this->tecnico);
        $id = $this->solicitar()->json('idsaidavidraria');

        $this->postJson("/api/saidavidrarias/{$id}/aprovar")->assertForbidden();
        $this->assertEquals(10, $this->bequer->fresh()->quantidade);
    }

    public function test_solicitacao_ja_decidida_nao_pode_ser_aprovada_de_novo(): void
    {
        Sanctum::actingAs($this->tecnico);
        $id = $this->solicitar()->json('idsaidavidraria');

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/saidavidrarias/{$id}/aprovar")->assertOk();
        $this->postJson("/api/saidavidrarias/{$id}/aprovar")->assertStatus(422);

        $this->assertEquals(8, $this->bequer->fresh()->quantidade);
    }

    public function test_aprovacao_recusada_se_estoque_ficou_insuficiente(): void
    {
        Sanctum::actingAs($this->tecnico);
        $id = $this->solicitar(['quantidade' => 8])->json('idsaidavidraria');
        $this->bequer->update(['quantidade' => 5]);

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/saidavidrarias/{$id}/aprovar")->assertStatus(422);
    }

    public function test_baixa_feita_pelo_admin_e_aprovada_na_hora(): void
    {
        Sanctum::actingAs($this->admin);

        $this->solicitar()->assertCreated()->assertJsonPath('status', 'aprovada');
        $this->assertEquals(8, $this->bequer->fresh()->quantidade);
    }

    public function test_solicitante_cancela_a_propria_pendente_mas_nao_a_de_outro(): void
    {
        Sanctum::actingAs($this->tecnico);
        $minha = $this->solicitar()->json('idsaidavidraria');

        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));
        $this->deleteJson("/api/saidavidrarias/{$minha}")->assertForbidden();

        Sanctum::actingAs($this->tecnico);
        $this->deleteJson("/api/saidavidrarias/{$minha}")->assertOk();
    }

    public function test_estorno_de_baixa_aprovada_so_pelo_admin_e_devolve_estoque(): void
    {
        Sanctum::actingAs($this->admin);
        $id = $this->solicitar()->json('idsaidavidraria');

        Sanctum::actingAs($this->tecnico);
        $this->deleteJson("/api/saidavidrarias/{$id}")->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/saidavidrarias/{$id}")->assertOk();
        $this->assertEquals(10, $this->bequer->fresh()->quantidade);
    }

    public function test_dashboard_mostra_pendentes(): void
    {
        Sanctum::actingAs($this->tecnico);
        $this->solicitar();
        $this->solicitar(['quantidade' => 1]);

        $this->getJson('/api/dashboard')
            ->assertJsonPath('baixas_vidraria_pendentes', 2)
            ->assertJsonPath('quebras_vidraria_30_dias', 0);
    }
}
