<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\vidraria;
use Illuminate\Http\Request;

class vidrariaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $vidrarias = vidraria::all();
        return response()->json($vidrarias, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);
        $vidraria = vidraria::create($request->all());
        return response()->json($vidraria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        return response()->json($vidraria, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        $vidraria->update($request->all());
        return response()->json($vidraria, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $vidraria = vidraria::find($id);
        if (!$vidraria) {
            return response()->json(['message' => 'Vidraria não encontrada'], 404);
        }
        $vidraria->delete();
        return response()->json(['message' => 'Vidraria excluída com sucesso'], 200);
    }
}
