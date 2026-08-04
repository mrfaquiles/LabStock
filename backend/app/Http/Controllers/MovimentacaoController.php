<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    // Listar todas as movimentações (com item e usuário)
    public function index()
    {
        return response()->json(Movimentacao::with(['item', 'user'])->get(), 200);
    }

    // Criar uma nova movimentação
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:itens,id',
            'user_id' => 'required|exists:users,id',
            'tipo_movimentacao' => 'required|in:baixa_quebra,emprestimo,consumo_reagente',
            'quantidade_movimentada' => 'nullable|numeric|min:0',
            'motivo_ou_destino' => 'required|string',
            'status_aprovacao' => 'sometimes|in:pendente,aprovado,rejeitado',
            'data_devolucao' => 'nullable|date',
        ]);

        $movimentacao = Movimentacao::create($validated);

        return response()->json([
            'message' => 'Movimentação registrada com sucesso!',
            'data' => $movimentacao
        ], 201);
    }

    // Exibir uma movimentação específica
    public function show($id)
    {
        $movimentacao = Movimentacao::with(['item', 'user'])->find($id);
        
        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }
        
        return response()->json($movimentacao, 200);
    }

    // Atualizar uma movimentação
    public function update(Request $request, $id)
    {
        $movimentacao = Movimentacao::find($id);

        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }

        $validated = $request->validate([
            'tipo_movimentacao' => 'sometimes|in:baixa_quebra,emprestimo,consumo_reagente',
            'quantidade_movimentada' => 'sometimes|numeric|min:0',
            'motivo_ou_destino' => 'sometimes|string',
            'status_aprovacao' => 'sometimes|in:pendente,aprovado,rejeitado',
            'data_devolucao' => 'nullable|date',
        ]);

        $movimentacao->update($validated);
        
        return response()->json([
            'message' => 'Movimentação atualizada com sucesso!',
            'data' => $movimentacao
        ], 200);
    }

    // Excluir uma movimentação
    public function destroy($id)
    {
        $movimentacao = Movimentacao::find($id);

        if (!$movimentacao) {
            return response()->json(['message' => 'Movimentação não encontrada'], 404);
        }

        $movimentacao->delete();
        return response()->json(['message' => 'Movimentação removida com sucesso'], 200);
    }
}