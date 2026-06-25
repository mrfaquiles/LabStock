<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    // 1. Lista todos os itens (GET /api/itens)
    public function index()
    {
        return Item::all();
    }

    // 2. Cria um novo item (POST /api/itens)
    public function store(Request $request)
    {
        // Nota: Altera os campos abaixo para bater com as colunas da tua tabela de itens
        $validated = $request->validate([
            'nome' => 'required|string',
            // 'descricao' => 'nullable|string',
        ]);

        return Item::create($validated);
    }

    // 3. Mostra um item específico (GET /api/itens/{id})
    public function show($id)
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }
        
        return $item;
    }

    // 4. Atualiza um item (PUT/PATCH /api/itens/{id})
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        // Altera também aqui conforme a tua tabela
        $validated = $request->validate([
            'nome' => 'sometimes|required|string',
        ]);

        $item->update($validated);
        return $item;
    }

    // 5. Exclui um item (DELETE /api/itens/{id})
    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item removido com sucesso']);
    }
}