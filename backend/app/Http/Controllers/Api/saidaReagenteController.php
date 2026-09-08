<?php

namespace App\Http\Controllers;
use App\Models\saidaReagente;
use Illuminate\Http\Request;

class saidaReagenteController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $saidaReagentes = saidaReagente::all();
        return response()->json($saidaReagentes, 200);
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
            'data_saida' => 'required|date',
        ]);
        $saidaReagente = saidaReagente::create($request->all());
        return response()->json($saidaReagente, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $saidaReagente = saidaReagente::find($id);
        if (!$saidaReagente) {
            return response()->json(['message' => 'Saída de reagente não encontrada'], 404);
        }
        return response()->json($saidaReagente, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $saidaReagente = saidaReagente::find($id);
        if (!$saidaReagente) {
            return response()->json(['message' => 'Saída de reagente não encontrada'], 404);
        }
        $saidaReagente->update($request->all());
        return response()->json($saidaReagente, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $saidaReagente = saidaReagente::find($id);
        if (!$saidaReagente) {
            return response()->json(['message' => 'Saída de reagente não encontrada'], 404);
        }
        $saidaReagente->delete();
        return response()->json(['message' => 'Saída de reagente excluída com sucesso'], 200);
    }
}
