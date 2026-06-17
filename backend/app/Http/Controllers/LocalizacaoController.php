<?php

namespace App\Http\Controllers;

use App\Models\Localizacao;
use Illuminate\Http\Request;

class LocalizacaoController extends Controller
{
    public function index()
    {
        return Localizacao::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_laboratorio' => 'required|integer',
            'sala' => 'required|string',
            'armario' => 'nullable|string',
            'prateleira' => 'nullable|string',
            'descricao' => 'nullable|string'
        ]);

        return Localizacao::create($validated);
    }

    public function show($id)
    {
        $localizacao = Localizacao::find($id);
        
        if (!$localizacao) {
            return response()->json(['message' => 'Localização não encontrada'], 404);
        }
        
        return $localizacao;
    }

    public function update(Request $request, $id)
    {
        $localizacao = Localizacao::find($id);

        if (!$localizacao) {
            return response()->json(['message' => 'Localização não encontrada'], 404);
        }

        $validated = $request->validate([
            'sala' => 'sometimes|required|string',
            'armario' => 'nullable|string',
            'prateleira' => 'nullable|string',
            'descricao' => 'nullable|string'
        ]);

        $localizacao->update($validated);
        return $localizacao;
    }

    public function destroy($id)
    {
        $localizacao = Localizacao::find($id);

        if (!$localizacao) {
            return response()->json(['message' => 'Localização não encontrada'], 404);
        }

        $localizacao->delete();
        return response()->json(['message' => 'Localização removida com sucesso']);
    }
}