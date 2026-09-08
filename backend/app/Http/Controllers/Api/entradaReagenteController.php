<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\entradaReagente;
use Illuminate\Http\Request;

class entradaReagenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $entradaReagentes = entradaReagente::all();
        return response()->json($entradaReagentes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'reagente_id' => 'required|exists:reagentes,id',
            'quantidade' => 'required|numeric|min:0',
            'data_entrada' => 'required|date',
        ]);
        $entradaReagente = entradaReagente::create($request->all());
        return response()->json($entradaReagente, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $entradaReagente = entradaReagente::find($id);
        if (!$entradaReagente) {
            return response()->json(['message' => 'Entrada de reagente não encontrada'], 404);
        }
        return response()->json($entradaReagente, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $entradaReagente = entradaReagente::find($id);
        if (!$entradaReagente) {
            return response()->json(['message' => 'Entrada de reagente não encontrada'], 404);
        }
        $entradaReagente->update($request->all());
        return response()->json($entradaReagente, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $entradaReagente = entradaReagente::find($id);
        if (!$entradaReagente) {
            return response()->json(['message' => 'Entrada de reagente não encontrada'], 404);
        }
        $entradaReagente->delete();
        return response()->json(['message' => 'Entrada de reagente excluída com sucesso'], 200);
    }
}
