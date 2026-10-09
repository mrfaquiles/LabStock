<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\unidade;
use Illuminate\Http\Request;

/**
 * Unidades (campus/sedes) da instituição.
 */
class unidadeController extends Controller
{
    public function index()
    {
        $unidades = unidade::withCount('laboratorios')->orderBy('nome')->get();
        return response()->json($unidades, 200);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:150|unique:unidades,nome',
            'endereco' => 'nullable|string|max:255',
        ]);
        return response()->json(unidade::create($dados), 201);
    }

    public function show(string $id)
    {
        $unidade = unidade::with('laboratorios')->find($id);
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }
        return response()->json($unidade, 200);
    }

    public function update(Request $request, string $id)
    {
        $unidade = unidade::find($id);
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }
        $dados = $request->validate([
            'nome' => "sometimes|required|string|max:150|unique:unidades,nome,{$id},idunidade",
            'endereco' => 'nullable|string|max:255',
        ]);
        $unidade->update($dados);
        return response()->json($unidade, 200);
    }

    /**
     * Unidades com laboratórios não podem ser excluídas (ver bootstrap/app.php).
     */
    public function destroy(string $id)
    {
        $unidade = unidade::find($id);
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }
        $unidade->delete();
        return response()->json(['message' => 'Unidade excluída com sucesso'], 200);
    }
}
