<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use Illuminate\Http\Request;

class EquipamentoController extends Controller
{
    public function index()
    {
        return Equipamento::all();
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string',
            'patrimonio' => 'required|string',
            'catmat' => 'nullable|string',
            'status' => 'required|string',
            'data_calibracao' => 'nullable|date',
            'localizacao' => 'nullable|string',
        ]);

        return Equipamento::create($dados);
    }

    public function update(Request $request, Equipamento $equipamento)
    {
        $dados = $request->validate([
            'nome' => 'required|string',
            'patrimonio' => 'required|string',
            'catmat' => 'nullable|string',
            'status' => 'required|string',
            'data_calibracao' => 'nullable|date',
            'localizacao' => 'nullable|string',
        ]);

        $equipamento->update($dados);
        return $equipamento;
    }

    public function destroy(Equipamento $equipamento)
    {
        $equipamento->delete();
        return response()->noContent();
    }
}