<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('nome')->get();
        return response()->json($usuarios, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'tipo' => ['required', Rule::in(User::PERFIS)],
            'ativo' => 'boolean',
        ]);

        $usuario = User::create($validated);
        return response()->json($usuario, 201);
    }

    public function show(string $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        return response()->json($usuario, 200);
    }

    public function update(Request $request, string $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8',
            'tipo' => ['sometimes', 'required', Rule::in(User::PERFIS)],
            'ativo' => 'boolean',
        ]);

        // Senha em branco na edição = manter a atual
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Impede que o admin remova o próprio acesso de administrador
        if ($usuario->id === $request->user()->id) {
            if ((isset($validated['tipo']) && $validated['tipo'] !== User::PERFIL_ADMIN)
                || (array_key_exists('ativo', $validated) && !$validated['ativo'])) {
                return response()->json(['message' => 'Você não pode remover o seu próprio acesso de administrador'], 422);
            }
        }

        $usuario->update($validated);

        // Usuário desativado perde as sessões abertas
        if (!$usuario->ativo) {
            $usuario->tokens()->delete();
        }

        return response()->json($usuario, 200);
    }

    public function destroy(Request $request, string $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'Você não pode excluir o seu próprio usuário'], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();
        return response()->json(['message' => 'Usuário removido com sucesso'], 200);
    }
}
