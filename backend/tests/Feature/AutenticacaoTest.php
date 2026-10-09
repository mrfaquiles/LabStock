<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $tipo, array $extra = []): User
    {
        return User::factory()->create(array_merge(['tipo' => $tipo, 'password' => 'senha-teste-123'], $extra));
    }

    public function test_login_com_credenciais_validas_retorna_token(): void
    {
        $usuario = $this->usuario('tecnico');

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'senha-teste-123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'nome', 'email', 'tipo']])
            ->assertJsonMissingPath('usuario.password');
    }

    public function test_login_com_senha_errada_retorna_401(): void
    {
        $usuario = $this->usuario('tecnico');

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'errada'])
            ->assertUnauthorized();
    }

    public function test_usuario_inativo_nao_faz_login(): void
    {
        $usuario = $this->usuario('tecnico', ['ativo' => 0]);

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'senha-teste-123'])
            ->assertForbidden();
    }

    public function test_login_bloqueia_apos_muitas_tentativas(): void
    {
        $usuario = $this->usuario('tecnico');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'errada']);
        }

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'senha-teste-123'])
            ->assertStatus(429);
    }

    public function test_rotas_protegidas_exigem_autenticacao(): void
    {
        $this->getJson('/api/reagentes')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_consulta_pode_listar_mas_nao_cadastrar(): void
    {
        Sanctum::actingAs($this->usuario('consulta'));

        $this->getJson('/api/reagentes')->assertOk();
        $this->postJson('/api/reagentes', ['nome' => 'Ácido Clorídrico'])->assertForbidden();
        $this->deleteJson('/api/reagentes/1')->assertForbidden();
    }

    public function test_tecnico_nao_acessa_gestao_de_usuarios(): void
    {
        Sanctum::actingAs($this->usuario('tecnico'));

        $this->getJson('/api/usuarios')->assertForbidden();
    }

    public function test_admin_cadastra_usuario_com_senha_criptografada(): void
    {
        Sanctum::actingAs($this->usuario('admin'));

        $this->postJson('/api/usuarios', [
            'nome' => 'Técnica do Lab',
            'email' => 'tecnica@labstock.local',
            'password' => 'senha-segura-1',
            'tipo' => 'tecnico',
        ])->assertCreated();

        $criado = User::where('email', 'tecnica@labstock.local')->first();
        $this->assertNotEquals('senha-segura-1', $criado->password);
        $this->assertTrue(password_verify('senha-segura-1', $criado->password));
    }

    public function test_admin_nao_remove_o_proprio_acesso(): void
    {
        $admin = $this->usuario('admin');
        Sanctum::actingAs($admin);

        $this->putJson("/api/usuarios/{$admin->id}", ['tipo' => 'consulta'])->assertStatus(422);
        $this->deleteJson("/api/usuarios/{$admin->id}")->assertStatus(422);
    }

    public function test_logout_revoga_token(): void
    {
        $usuario = $this->usuario('tecnico');
        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertCount(0, $usuario->tokens()->get());
    }
}
