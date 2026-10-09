<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\saida_vidraria;
use App\Models\vidraria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Solicitações de baixa de vidraria (quebra, perda, defeito, descarte).
 *
 * Fluxo: qualquer usuário solicita informando motivo e justificativa; o estoque
 * só diminui quando um administrador aprova. Solicitações do próprio admin são
 * aprovadas na hora. Nada é apagado do cadastro: o histórico fica registrado.
 */
class saidaVidrariaController extends Controller
{
    private array $relacoes = ['vidraria', 'laboratorio', 'usuario:id,nome,email', 'aprovador:id,nome'];

    /**
     * Lista as solicitações, pendentes primeiro.
     * Filtros opcionais: ?status=pendente  ?idvidraria=3
     */
    public function index(Request $request)
    {
        $consulta = saida_vidraria::with($this->relacoes)
            ->orderByRaw("CASE WHEN status = 'pendente' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $consulta->where('status', $request->input('status'));
        }
        if ($request->filled('idvidraria')) {
            $consulta->where('idvidraria', $request->input('idvidraria'));
        }

        return response()->json($consulta->get(), 200);
    }

    public function show(string $id)
    {
        $saida = saida_vidraria::with($this->relacoes)->find($id);
        if (!$saida) {
            return response()->json(['message' => 'Solicitação de baixa não encontrada'], 404);
        }
        return response()->json($saida, 200);
    }

    /**
     * Abre uma solicitação de baixa (qualquer perfil).
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'idvidraria' => 'required|exists:vidrarias,idvidraria',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1',
            'motivo' => ['required', Rule::in(saida_vidraria::MOTIVOS)],
            'observacao' => 'required|string|min:10|max:255',
            'data' => 'nullable|date',
        ], [
            'observacao.required' => 'A justificativa da baixa é obrigatória.',
            'observacao.min' => 'Descreva a justificativa com pelo menos 10 caracteres.',
        ]);

        $usuario = $request->user();

        return DB::transaction(function () use ($dados, $usuario) {
            $vidraria = vidraria::lockForUpdate()->find($dados['idvidraria']);

            if ($dados['quantidade'] > $vidraria->quantidade) {
                return response()->json([
                    'message' => "Quantidade maior que o estoque. Disponível: {$vidraria->quantidade} un.",
                ], 422);
            }

            $dados['idusuario'] = $usuario->id;
            $dados['data'] = $dados['data'] ?? now()->toDateString();
            $dados['status'] = saida_vidraria::PENDENTE;

            // Admin não precisa aprovar a própria baixa (nem ninguém, se a aprovação estiver desligada)
            if ($usuario->isAdmin() || !Configuracao::valor('vidraria_exige_aprovacao')) {
                $dados['status'] = saida_vidraria::APROVADA;
                $dados['idaprovador'] = $usuario->id;
                $dados['data_decisao'] = now();
                $vidraria->decrement('quantidade', $dados['quantidade']);
            }

            $saida = saida_vidraria::create($dados);
            return response()->json($saida->load($this->relacoes), 201);
        });
    }

    /**
     * Admin aprova: a quantidade sai do estoque.
     */
    public function aprovar(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $saida = saida_vidraria::lockForUpdate()->find($id);
            if (!$saida) {
                return response()->json(['message' => 'Solicitação de baixa não encontrada'], 404);
            }
            if ($saida->status !== saida_vidraria::PENDENTE) {
                return response()->json(['message' => "Esta solicitação já foi {$saida->status}."], 422);
            }

            // O estoque pode ter mudado desde a solicitação
            $vidraria = vidraria::lockForUpdate()->find($saida->idvidraria);
            if ($saida->quantidade > $vidraria->quantidade) {
                return response()->json([
                    'message' => "Estoque insuficiente para aprovar. Disponível: {$vidraria->quantidade} un.",
                ], 422);
            }

            $vidraria->decrement('quantidade', $saida->quantidade);
            $saida->update([
                'status' => saida_vidraria::APROVADA,
                'idaprovador' => $request->user()->id,
                'data_decisao' => now(),
            ]);

            return response()->json($saida->load($this->relacoes), 200);
        });
    }

    /**
     * Admin recusa informando o motivo. O estoque não muda.
     */
    public function recusar(Request $request, string $id)
    {
        $dados = $request->validate([
            'motivo_recusa' => 'required|string|min:5|max:255',
        ], [
            'motivo_recusa.required' => 'Informe o motivo da recusa.',
            'motivo_recusa.min' => 'Descreva o motivo da recusa com pelo menos 5 caracteres.',
        ]);

        $saida = saida_vidraria::find($id);
        if (!$saida) {
            return response()->json(['message' => 'Solicitação de baixa não encontrada'], 404);
        }
        if ($saida->status !== saida_vidraria::PENDENTE) {
            return response()->json(['message' => "Esta solicitação já foi {$saida->status}."], 422);
        }

        $saida->update([
            'status' => saida_vidraria::RECUSADA,
            'idaprovador' => $request->user()->id,
            'data_decisao' => now(),
            'motivo_recusa' => $dados['motivo_recusa'],
        ]);

        return response()->json($saida->load($this->relacoes), 200);
    }

    /**
     * Pendente: quem solicitou (ou o admin) pode cancelar.
     * Aprovada: só o admin pode estornar (a quantidade volta ao estoque).
     * Recusada: fica no histórico.
     */
    public function destroy(Request $request, string $id)
    {
        $usuario = $request->user();

        return DB::transaction(function () use ($usuario, $id) {
            $saida = saida_vidraria::lockForUpdate()->find($id);
            if (!$saida) {
                return response()->json(['message' => 'Solicitação de baixa não encontrada'], 404);
            }

            if ($saida->status === saida_vidraria::PENDENTE) {
                if (!$usuario->isAdmin() && (int) $saida->idusuario !== (int) $usuario->id) {
                    return response()->json(['message' => 'Só quem fez a solicitação pode cancelá-la.'], 403);
                }
                $saida->delete();
                return response()->json(['message' => 'Solicitação cancelada'], 200);
            }

            if ($saida->status === saida_vidraria::APROVADA) {
                if (!$usuario->isAdmin()) {
                    return response()->json(['message' => 'Apenas o administrador pode estornar uma baixa aprovada.'], 403);
                }
                vidraria::lockForUpdate()->find($saida->idvidraria)->increment('quantidade', $saida->quantidade);
                $saida->delete();
                return response()->json(['message' => 'Baixa estornada: a quantidade voltou ao estoque'], 200);
            }

            return response()->json(['message' => 'Solicitações recusadas ficam no histórico e não podem ser excluídas.'], 422);
        });
    }
}
