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
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'catmat' => 'required|string|max:50',
            'patrimonio' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'quantidade' => 'nullable|integer|min:0',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ]);
        $dados['quantidade'] = $dados['quantidade'] ?? 1;
        $equipamento = equipamento::create($dados);
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
        $dados = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'catmat' => 'sometimes|required|string|max:50',
            'patrimonio' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'quantidade' => 'sometimes|integer|min:0',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ]);
        $equipamento->update($dados);
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
