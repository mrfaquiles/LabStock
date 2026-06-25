<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index()
    {
        return Fornecedor::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string',
            'cnpj' => 'required|string',
            'email' => 'nullable|email',
        ]);

        return Fornecedor::create($validated);
    }

    public function show(Fornecedor $fornecedor)
    {
        return $fornecedor;
    }

    public function update(Request $request, Fornecedor $fornecedor)
    {
        $validated = $request->validate([
            'nome' => 'sometimes|required|string',
            'cnpj' => 'sometimes|required|string',
            'email' => 'nullable|email',
        ]);

        $fornecedor->update($validated);
        return $fornecedor;
    }

    public function destroy(Fornecedor $fornecedor)
    {
        return $fornecedor->delete() 
            ? response()->json(['message' => 'Removido com sucesso']) 
            : response()->json(['message' => 'Erro ao remover'], 500);
    }
}