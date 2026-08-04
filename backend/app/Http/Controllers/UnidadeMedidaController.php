<?php

namespace App\Http\Controllers;

use App\Models\UnidadeMedida;
use Illuminate\Http\Request;

class UnidadeMedidaController extends Controller
{
    // Listar todas as unidades de medida
    public function index()
    {
        return response()->json(UnidadeMedida::all(), 200);
    }

    // Criar uma nova unidade de medida
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sigla' => 'required|string|max:10|unique:unidades_medida,sigla',
            'descricao' => 'required|string|max:50',
        ]);

        $unidade = UnidadeMedida::create($validated);

        return response()->json([
            'message' => 'Unidade de medida cadastrada com sucesso!',
            'data' => $unidade
        ], 201);
    }

    // Exibir uma unidade específica
    public function show($id)
    {
        $unidade = UnidadeMedida::find($id);

        if (!$unidade) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }

        return response()->json($unidade, 200);
    }

    // Atualizar uma unidade
    public function update(Request $request, $id)
    {
        $unidade = UnidadeMedida::find($id);

        if (!$unidade) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }

        $validated = $request->validate([
            'sigla' => 'sometimes|string|max:10|unique:unidades_medida,sigla,' . $id,
            'descricao' => 'sometimes|string|max:50',
        ]);

        $unidade->update($validated);

        return response()->json([
            'message' => 'Unidade de medida atualizada com sucesso!',
            'data' => $unidade
        ], 200);
    }

    // Deletar uma unidade
    public function destroy($id)
    {
        $unidade = UnidadeMedida::find($id);

        if (!$unidade) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }

        $unidade->delete();

        return response()->json(['message' => 'Unidade de medida removida com sucesso!'], 200);
    }
}