<?php

namespace App\Http\Controllers;

use App\Models\LoteReagente;
use Illuminate\Http\Request;

class LoteReagenteController extends Controller
{
    // Listar todos os lotes (com o item correspondente)
    public function index()
    {
        return response()->json(LoteReagente::with('item')->get(), 200);
    }

    // Criar um novo lote de reagente
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:itens,id',
            'numero_lote' => 'required|string|max:50',
            'data_validade' => 'required|date',
            'quantidade_atual' => 'required|numeric|min:0',
            'local_armazenamento' => 'required|string|max:100',
        ]);

        $lote = LoteReagente::create($validated);

        return response()->json([
            'message' => 'Lote cadastrado com sucesso!',
            'data' => $lote
        ], 201);
    }

    // Exibir um lote específico
    public function show($id)
    {
        $lote = LoteReagente::with('item')->find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }

        return response()->json($lote, 200);
    }

    // Atualizar um lote
    public function update(Request $request, $id)
    {
        $lote = LoteReagente::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }

        $validated = $request->validate([
            'numero_lote' => 'sometimes|string|max:50',
            'data_validade' => 'sometimes|date',
            'quantidade_atual' => 'sometimes|numeric|min:0',
            'local_armazenamento' => 'sometimes|string|max:100',
        ]);

        $lote->update($validated);

        return response()->json([
            'message' => 'Lote atualizado com sucesso!',
            'data' => $lote
        ], 200);
    }

    // Deletar um lote
    public function destroy($id)
    {
        $lote = LoteReagente::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }

        $lote->delete();

        return response()->json(['message' => 'Lote removido com sucesso!'], 200);
    }
}