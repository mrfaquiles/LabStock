<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\unidade_medida;
use Illuminate\Http\Request;

class UnidadeMedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unidadeMedidas = unidade_medida::all();
        return response()->json($unidadeMedidas, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);
        $unidadeMedida = unidade_medida::create($request->all());
        return response()->json($unidadeMedida, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $unidadeMedida = unidade_medida::find($id);
        if (!$unidadeMedida) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }
        return response()->json($unidadeMedida, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $unidadeMedida = unidade_medida::find($id);
        if (!$unidadeMedida) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }
        $unidadeMedida->update($request->all());
        return response()->json($unidadeMedida, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $unidadeMedida = unidade_medida::find($id);
        if (!$unidadeMedida) {
            return response()->json(['message' => 'Unidade de medida não encontrada'], 404);
        }
        $unidadeMedida->delete();
        return response()->json(['message' => 'Unidade de medida excluída com sucesso'], 200);
    }
}
