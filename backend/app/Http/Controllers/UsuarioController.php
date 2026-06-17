<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        return Usuario::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string',
            'email' => 'required|email|unique:usuario,email',
            'senha_hash' => 'required|string',
            'tipo_usuario' => 'required|string',
            'ativo' => 'boolean'
        ]);

        return Usuario::create($validated);
    }

    public function show($id)
    {
        $usuario = Usuario::find($id);
        
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
        
        return $usuario;
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string',
            'tipo_usuario' => 'sometimes|required|string',
            'ativo' => 'boolean'
        ]);

        $usuario->update($validated);
        return $usuario;
    }

    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $usuario->delete();
        return response()->json(['message' => 'Usuário removido com sucesso']);
    }
}