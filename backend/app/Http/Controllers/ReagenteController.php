<?php

namespace App\Http\Controllers;

use App\Models\Reagente;
use Illuminate\Http\Request;

class ReagenteController extends Controller
{
    public function index()
    {
        return Reagente::all();
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string',
            'formula_quimica' => 'nullable|string',
            'cas_number' => 'nullable|string',
        ]);

        return Reagente::create($dados);
    }

    public function update(Request $request, Reagente $reagente)
    {
        $dados = $request->validate([
            'nome' => 'required|string',
            'formula_quimica' => 'nullable|string',
            'cas_number' => 'nullable|string',
        ]);

        $reagente->update($dados);
        return $reagente;
    }

    public function destroy(Reagente $reagente)
    {
        $reagente->delete();
        return response()->noContent();
    }
}