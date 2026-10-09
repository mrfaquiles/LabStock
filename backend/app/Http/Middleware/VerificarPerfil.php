<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota aos perfis informados.
 * Uso: ->middleware('perfil:admin,tecnico')
 */
class VerificarPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->ativo) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        if (!$usuario->temPerfil(...$perfis)) {
            return response()->json(['message' => 'Você não tem permissão para realizar esta ação'], 403);
        }

        return $next($request);
    }
}
