<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    /**
     * Configurações atuais (todos os perfis leem: o frontend usa nos relatórios e formulários).
     */
    public function index()
    {
        return response()->json(Configuracao::todas(), 200);
    }

    /**
     * Atualiza as configurações (apenas administrador).
     */
    public function update(Request $request)
    {
        $dados = $request->validate([
            'instituicao_nome' => 'sometimes|required|string|max:150',
            'laboratorio_nome' => 'sometimes|required|string|max:150',
            'responsavel_tecnico' => 'nullable|string|max:150',
            'meses_alerta_padrao' => 'sometimes|required|integer|min:1|max:36',
            'cobertura_minima_meses' => 'sometimes|required|integer|min:1|max:24',
            'vidraria_exige_aprovacao' => 'sometimes|boolean',
            'sessao_horas' => 'sometimes|required|integer|min:1|max:72',
        ], [], [
            'instituicao_nome' => 'nome da instituição',
            'laboratorio_nome' => 'nome do laboratório',
            'responsavel_tecnico' => 'responsável técnico',
            'meses_alerta_padrao' => 'alerta de vencimento padrão',
            'cobertura_minima_meses' => 'cobertura mínima',
            'sessao_horas' => 'duração da sessão',
        ]);

        Configuracao::salvar($dados);

        return response()->json(Configuracao::todas(), 200);
    }
}
