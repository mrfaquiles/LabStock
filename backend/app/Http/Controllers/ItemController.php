<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    // 1. Lista todos os itens (com a unidade de medida junto)
    public function index()
    {
        return response()->json(Item::with('unidadeMedida')->get(), 200);
    }

    // 2. Cria um novo item (POST /api/itens)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_cat' => 'required|string|unique:itens,codigo_cat',
            'nome_curto' => 'required|string|max:100',
            'descricao_longa' => 'required|string',
            'categoria' => 'required|in:Vidraria,Equipamento,Reagente',
            'unidade_medida_id' => 'required|exists:unidades_medida,id',
        ]);

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Item cadastrado com sucesso!',
            'data' => $item
        ], 201);
    }

    // 3. Mostra um item específico (GET /api/itens/{id})
    public function show($id)
    {
        $item = Item::with(['unidadeMedida', 'lotes', 'movimentacoes'])->find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }
        
        return response()->json($item, 200);
    }

    // 4. Atualiza um item (PUT/PATCH /api/itens/{id})
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $validated = $request->validate([
            'codigo_cat' => 'sometimes|string|unique:itens,codigo_cat,' . $id . ',id',
            'nome_curto' => 'sometimes|string|max:100',
            'descricao_longa' => 'sometimes|string',
            'categoria' => 'sometimes|in:Vidraria,Equipamento,Reagente',
            'unidade_medida_id' => 'sometimes|exists:unidades_medida,id',
        ]);

        $item->update($validated);
        
        return response()->json([
            'message' => 'Item atualizado com sucesso!',
            'data' => $item
        ], 200);
    }

    // 5. Exclui um item (DELETE /api/itens/{id})
    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item removido com sucesso'], 200);
    }
}