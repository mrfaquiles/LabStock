<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\laboratorio;
use Illuminate\Http\Request;

class laboratorioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $laboratorios = laboratorio::with('unidade')->orderBy('nome')->get();
        return response()->json($laboratorios, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'idunidade' => 'nullable|exists:unidades,idunidade',
        ]);
        $laboratorio = laboratorio::create($dados);
        return response()->json($laboratorio->load('unidade'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $laboratorio = laboratorio::with('unidade')->find($id);
        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }
        return response()->json($laboratorio, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $laboratorio = laboratorio::find($id);
        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'idunidade' => 'nullable|exists:unidades,idunidade',
        ]);
        $laboratorio->update($dados);
        return response()->json($laboratorio->load('unidade'), 200);
    }

    /**
     * Laboratórios em uso não podem ser excluídos (ver bootstrap/app.php).
     */
    public function destroy(string $id)
    {
        $laboratorio = laboratorio::find($id);
        if (!$laboratorio) {
            return response()->json(['message' => 'Laboratório não encontrado'], 404);
        }
        $laboratorio->delete();
        return response()->json(['message' => 'Laboratório excluído com sucesso'], 200);
    }
}
