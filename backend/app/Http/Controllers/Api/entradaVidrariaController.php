<?php

namespace App\Http\Controllers;
use App\Models\entradaVidraria;
use Illuminate\Http\Request;

class entradaVidrariaController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entradaVidrarias = entradaVidraria::all();
        return response()->json($entradaVidrarias, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'vidraria_id' => 'required|exists:vidrarias,id',
            'quantidade' => 'required|numeric|min:0',
            'data_entrada' => 'required|date',
        ]);
        $entradaVidraria = entradaVidraria::create($request->all());
        return response()->json($entradaVidraria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $entradaVidraria = entradaVidraria::find($id);
        if (!$entradaVidraria) {
            return response()->json(['message' => 'Entrada de vidraria não encontrada'], 404);
        }
        return response()->json($entradaVidraria, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $entradaVidraria = entradaVidraria::find($id);
        if (!$entradaVidraria) {
            return response()->json(['message' => 'Entrada de vidraria não encontrada'], 404);
        }
        $entradaVidraria->update($request->all());
        return response()->json($entradaVidraria, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $entradaVidraria = entradaVidraria::find($id);
        if (!$entradaVidraria) {
            return response()->json(['message' => 'Entrada de vidraria não encontrada'], 404);
        }
        $entradaVidraria->delete();
        return response()->json(['message' => 'Entrada de vidraria excluída com sucesso'], 200);
    }
}
