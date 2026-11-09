<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\entrada_vidraria;
use App\Models\vidraria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class vidrariaController extends Controller
{
    private const MENSAGENS = [
        'nome.unique' => 'Já existe uma vidraria com este nome.',
        'catmat.unique' => 'Já existe uma vidraria com este CATMAT.',
    ];

    public function index()
    {
        //
        $vidrarias = vidraria::with('laboratorio.unidade')->orderBy('nome')->get();
        return response()->json($vidrarias, 200);
    }

    public function store(Request $request)
    {
        //
        $dados = $request->validate([
            'nome' => 'required|string|max:255|unique:vidrarias,nome',
            'catmat' => 'required|string|max:50|unique:vidrarias,catmat',
            'quantidade' => 'nullable|integer|min:0|max:100000',
            'descricao' => 'nullable|string',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'localizacao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ], self::MENSAGENS);
        $quantidade = (int) ($dados['quantidade'] ?? 0);

        // O estoque inicial vira uma entrada, para o saldo sempre bater com as movimentações
        $vidraria = DB::transaction(function () use ($dados, $quantidade, $request) {
            $vidraria = vidraria::create(array_merge($dados, ['quantidade' => $quantidade]));
            if ($quantidade > 0) {
                entrada_vidraria::create([
                    'idvidraria' => $vidraria->idvidraria,
                    'idlaboratorio' => $vidraria->idlaboratorio,
                    'idusuario' => $request->user()->id,
                    'quantidade' => $quantidade,
                    'data' => now(),
                    'observacao' => 'Estoque inicial (cadastro)',
                ]);
            }
            return $vidraria;
        });
        return response()->json($vidraria->load('laboratorio.unidade'), 201);
    }

    public function show(string $id)
    {
        //
        $vidraria = vidraria::with('laboratorio.unidade')->find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        return response()->json($vidraria, 200);
    }

    public function update(Request $request, string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        $dados = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('vidrarias', 'nome')->ignore($vidraria->idvidraria, 'idvidraria')],
            'catmat' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('vidrarias', 'catmat')->ignore($vidraria->idvidraria, 'idvidraria')],
            // Sem "quantidade": o estoque só muda por entrada ou baixa aprovada
            'descricao' => 'nullable|string',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'localizacao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ], self::MENSAGENS);
        $vidraria->update($dados);
        return response()->json($vidraria->load('laboratorio.unidade'), 200);
    }

    public function destroy(string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        // Cadastro feito por engano: sem baixas e só com a entrada inicial, sai tudo junto.
        // Com outras movimentações, o banco recusa e a mensagem pede para desativar (bootstrap/app.php).
        $soEstoqueInicial = !$vidraria->saidas()->exists()
            && !$vidraria->entradas()->where(fn ($q) => $q->whereNull('observacao')
                ->orWhere('observacao', '!=', 'Estoque inicial (cadastro)'))->exists();

        DB::transaction(function () use ($vidraria, $soEstoqueInicial) {
            if ($soEstoqueInicial) {
                $vidraria->entradas()->delete();
            }
            $vidraria->delete();
        });
        return response()->json(['message' => 'Vidraria excluída com sucesso'], 200);
    }
}
