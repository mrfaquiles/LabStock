<?php

namespace App\Http\Controllers;
use App\Models\entradaEquipamento;
use Illuminate\Http\Request;

class entradaEquipamentoController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $entradaEquipamentos = entradaEquipamento::all();
        return response()->json($entradaEquipamentos, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'equipamento_id' => 'required|exists:equipamentos,id',
            'quantidade' => 'required|numeric|min:0',
            'data_entrada' => 'required|date',
        ]);
        $entradaEquipamento = entradaEquipamento::create($request->all());
        return response()->json($entradaEquipamento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $entradaEquipamento = entradaEquipamento::find($id);
        if (!$entradaEquipamento) {
            return response()->json(['message' => 'Entrada de equipamento não encontrada'], 404);
        }
        return response()->json($entradaEquipamento, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $entradaEquipamento = entradaEquipamento::find($id);
        if (!$entradaEquipamento) {
            return response()->json(['message' => 'Entrada de equipamento não encontrada'], 404);
        }
        $entradaEquipamento->update($request->all());
        return response()->json($entradaEquipamento, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $entradaEquipamento = entradaEquipamento::find($id);
        if (!$entradaEquipamento) {
            return response()->json(['message' => 'Entrada de equipamento não encontrada'], 404);
        }
        $entradaEquipamento->delete();
        return response()->json(['message' => 'Entrada de equipamento excluída com sucesso'], 200);
    }
}
