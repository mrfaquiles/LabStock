<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\saidaEquipamento;
use Illuminate\Http\Request;

class saidaEquipamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $saidaEquipamentos = saidaEquipamento::all();
        return response()->json($saidaEquipamentos, 200);
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
            'data_saida' => 'required|date',
        ]);
        $saidaEquipamento = saidaEquipamento::create($request->all());
        return response()->json($saidaEquipamento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $saidaEquipamento = saidaEquipamento::find($id);
        if (!$saidaEquipamento) {
            return response()->json(['message' => 'Saída de equipamento não encontrada'], 404);
        }
        return response()->json($saidaEquipamento, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $saidaEquipamento = saidaEquipamento::find($id);
        if (!$saidaEquipamento) {
            return response()->json(['message' => 'Saída de equipamento não encontrada'], 404);
        }
        $saidaEquipamento->update($request->all());
        return response()->json($saidaEquipamento, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $saidaEquipamento = saidaEquipamento::find($id);
        if (!$saidaEquipamento) {
            return response()->json(['message' => 'Saída de equipamento não encontrada'], 404);
        }
        $saidaEquipamento->delete();
        return response()->json(['message' => 'Saída de equipamento excluída com sucesso'], 200);
    }
}
