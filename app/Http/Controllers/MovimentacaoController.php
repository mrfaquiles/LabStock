<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    public function index()
    {
        return Movimentacao::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|integer',
            'id_lote' => 'nullable|integer',
            'id_usuario' => 'required|integer',
            'tipo_movimentacao' => 'required|string', 
            'quantidade' => 'required|integer',
            'data_movimentacao' => 'required|date',
            'motivo' => 'nullable|string'
        ]);

        return Movimentacao::create($validated);
    }

    public function show($id)
    {
        $movimentacao = Movimentacao::find($id);
        
        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }
        
        return $movimentacao;
    }

    public function update(Request $request, $id)
    {
        $movimentacao = Movimentacao::find($id);

        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }

        $validated = $request->validate([
            'tipo_movimentacao' => 'sometimes|required|string',
            'quantidade' => 'sometimes|required|integer',
            'motivo' => 'nullable|string'
        ]);

        $movimentacao->update($validated);
        return $movimentacao;
    }

    public function destroy($id)
    {
        $movimentacao = Movimentacao::find($id);

        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }

        $movimentacao->delete();
        return response()->json(['message' => 'Movimentação removida com sucesso']);
    }
}