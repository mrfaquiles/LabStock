<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\vidraria;
use Illuminate\Http\Request;

class vidrariaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $vidrarias = vidraria::with('laboratorio.unidade')->orderBy('nome')->get();
        return response()->json($vidrarias, 200);
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
            'quantidade' => 'nullable|integer|min:0',
            'descricao' => 'nullable|string',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'localizacao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ]);
        $dados['quantidade'] = $dados['quantidade'] ?? 0;
        $vidraria = vidraria::create($dados);
        return response()->json($vidraria->load('laboratorio.unidade'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $vidraria = vidraria::with('laboratorio.unidade')->find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        return response()->json($vidraria, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        $dados = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'catmat' => 'sometimes|required|string|max:50',
            'quantidade' => 'sometimes|integer|min:0',
            'descricao' => 'nullable|string',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'localizacao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ]);
        $vidraria->update($dados);
        return response()->json($vidraria->load('laboratorio.unidade'), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        $vidraria->delete();
        return response()->json(['message' => 'Vidraria excluída com sucesso'], 200);
    }
}
