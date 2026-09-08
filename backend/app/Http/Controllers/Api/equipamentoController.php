<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\equipamento;
use Illuminate\Http\Request;

class equipamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $equipamentos = equipamento::all();
        return response()->json($equipamentos, 200);
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
        $equipamento = equipamento::create($request->all());
        return response()->json($equipamento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }
        return response()->json($equipamento, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }
        $equipamento->update($request->all());
        return response()->json($equipamento, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }
        $equipamento->delete();
        return response()->json(['message' => 'Equipamento excluído com sucesso'], 200);
    }
}
