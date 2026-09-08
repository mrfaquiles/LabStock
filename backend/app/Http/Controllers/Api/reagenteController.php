<?php

namespace App\Http\Controllers;
use App\Models\reagente;
use Illuminate\Http\Request;

class reagenteController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $reagentes = reagente::all();
        return response()->json($reagentes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);
        $reagente = reagente::create($request->all());
        return response()->json($reagente, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }
        return response()->json($reagente, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }
        $reagente->update($request->all());
        return response()->json($reagente, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }
        $reagente->delete();
        return response()->json(['message' => 'Reagente excluído com sucesso'], 200);
    }
}
