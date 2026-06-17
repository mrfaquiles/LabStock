<?php

namespace App\Http\Controllers;

use App\Models\HistoricoDescarte;
use Illuminate\Http\Request;

class HistoricoDescarteController extends Controller
{
    public function index()
    {
        return HistoricoDescarte::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_item' => 'required|integer',
            'id_usuario' => 'required|integer',
            'quantidade' => 'required|integer',
            'motivo' => 'required|string',
            'data_descarte' => 'required|date'
        ]);

        return HistoricoDescarte::create($validated);
    }

    public function show($id)
    {
        $descarte = HistoricoDescarte::find($id);
        
        if (!$descarte) {
            return response()->json(['message' => 'Descarte não encontrado'], 404);
        }
        
        return $descarte;
    }

    public function update(Request $request, $id)
    {
        $descarte = HistoricoDescarte::find($id);

        if (!$descarte) {
            return response()->json(['message' => 'Descarte não encontrado'], 404);
        }

        $validated = $request->validate([
            'quantidade' => 'sometimes|required|integer',
            'motivo' => 'sometimes|required|string',
            'data_descarte' => 'sometimes|required|date'
        ]);

        $descarte->update($validated);
        return $descarte;
    }

    public function destroy($id)
    {
        $descarte = HistoricoDescarte::find($id);

        if (!$descarte) {
            return response()->json(['message' => 'Descarte não encontrado'], 404);
        }

        $descarte->delete();
        return response()->json(['message' => 'Descarte removido com sucesso']);
    }
}