<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function index()
    {
        return Estoque::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|integer',
            'id_localizacao' => 'required|integer',
            'quantidade_total' => 'required|integer'
        ]);

        return Estoque::create($validated);
    }

    public function show($id)
    {
        $estoque = Estoque::find($id);
        
        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }
        
        return $estoque;
    }

    public function update(Request $request, $id)
    {
        $estoque = Estoque::find($id);

        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $validated = $request->validate([
            'quantidade_total' => 'sometimes|required|integer'
        ]);

        $estoque->update($validated);
        return $estoque;
    }

    public function destroy($id)
    {
        $estoque = Estoque::find($id);

        if (!$estoque) {
            return response()->json(['message' => 'Estoque não encontrado'], 404);
        }

        $estoque->delete();
        return response()->json(['message' => 'Estoque removido com sucesso']);
    }
}