<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    public function index()
    {
        return Lote::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|integer',
            'numero_lote' => 'required|string',
            'data_validade' => 'required|date',
            'data_entrada' => 'required|date',
            'quantidade_inicial' => 'required|integer',
            'quantidade_atual' => 'required|integer'
        ]);

        return Lote::create($validated);
    }

    public function show($id)
    {
        $lote = Lote::find($id);
        
        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }
        
        return $lote;
    }

    public function update(Request $request, $id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }

        $validated = $request->validate([
            'quantidade_atual' => 'sometimes|required|integer'
        ]);

        $lote->update($validated);
        return $lote;
    }

    public function destroy($id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote não encontrado'], 404);
        }

        $lote->delete();
        return response()->json(['message' => 'Lote removido com sucesso']);
    }
}