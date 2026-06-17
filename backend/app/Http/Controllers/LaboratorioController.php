<?php

namespace App\Http\Controllers;

use App\Models\Laboratorio;
use Illuminate\Http\Request;

class LaboratorioController extends Controller
{
    public function index()
    {
        return Laboratorio::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string',
            'descricao' => 'nullable|string'
            // Adicione outras validações aqui se sua tabela tiver mais campos
        ]);

        return Laboratorio::create($validated);
    }

    public function show($id)
    {
        $laboratorio = Laboratorio::find($id);
        
        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }
        
        return $laboratorio;
    }

    public function update(Request $request, $id)
    {
        $laboratorio = Laboratorio::find($id);

        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string',
            'descricao' => 'nullable|string'
        ]);

        $laboratorio->update($validated);
        return $laboratorio;
    }

    public function destroy($id)
    {
        $laboratorio = Laboratorio::find($id);

        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }

        $laboratorio->delete();
        return response()->json(['message' => 'Laboratório removido com sucesso']);
    }
}