<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index()
    {
        return Produto::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string',
            'quantidade' => 'required|integer',
            'preco' => 'nullable|numeric',
        ]);

        return Produto::create($validated);
    }

    public function show(Produto $produto)
    {
        return $produto;
    }

    public function update(Request $request, Produto $produto)
    {
        $validated = $request->validate([
            'nome' => 'sometimes|required|string',
            'quantidade' => 'sometimes|required|integer',
            'preco' => 'nullable|numeric',
        ]);

        $produto->update($validated);
        return $produto;
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();
        return response()->json(['message' => 'Produto removido com sucesso']);
    }
}