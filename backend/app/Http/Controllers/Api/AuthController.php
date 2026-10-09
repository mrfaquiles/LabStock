<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_TENTATIVAS = 5;

    /**
     * Autentica o usuário e devolve um token de acesso (Sanctum).
     */
    public function login(Request $request)
    {
        $credenciais = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Bloqueio por excesso de tentativas (por e-mail + IP)
        $chave = Str::lower($credenciais['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($chave, self::MAX_TENTATIVAS)) {
            $segundos = RateLimiter::availableIn($chave);
            return response()->json([
                'message' => "Muitas tentativas de login. Tente novamente em {$segundos} segundos.",
            ], 429);
        }

        $usuario = User::where('email', $credenciais['email'])->first();

        if (!$usuario || !Hash::check($credenciais['password'], $usuario->password)) {
            RateLimiter::hit($chave, 60);
            return response()->json(['message' => 'E-mail ou senha inválidos'], 401);
        }

        if (!$usuario->ativo) {
            return response()->json(['message' => 'Usuário inativo. Procure o administrador do sistema.'], 403);
        }

        RateLimiter::clear($chave);

        // Validade do login definida em Configurações do Sistema
        $token = $usuario->createToken('labstock-spa', ['*'], now()->addHours(Configuracao::valor('sessao_horas')))->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => $usuario,
        ], 200);
    }

    /**
     * Dados do usuário autenticado.
     */
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    /**
     * Revoga o token atual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado com sucesso'], 200);
    }
}
