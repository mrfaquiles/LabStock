<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\saidaVidraria;
use Illuminate\Http\Request;

class saidaVidrariaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $saidaVidrarias = saidaVidraria::all();
        return response()->json($saidaVidrarias, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'vidraria_id' => 'required|exists:vidrarias,id',
            'quantidade' => 'required|numeric|min:0',
            'data_saida' => 'required|date',
        ]);
        $saidaVidraria = saidaVidraria::create($request->all());
        return response()->json($saidaVidraria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $saidaVidraria = saidaVidraria::find($id);
        if (!$saidaVidraria) {
            return response()->json(['message' => 'Saída de vidraria não encontrada'], 404);
        }
        return response()->json($saidaVidraria, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $saidaVidraria = saidaVidraria::find($id);
        if (!$saidaVidraria) {
            return response()->json(['message' => 'Saída de vidraria não encontrada'], 404);
        }
        $saidaVidraria->update($request->all());
        return response()->json($saidaVidraria, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $saidaVidraria = saidaVidraria::find($id);
        if (!$saidaVidraria) {
            return response()->json(['message' => 'Saída de vidraria não encontrada'], 404);
        }
        $saidaVidraria->delete();
        return response()->json(['message' => 'Saída de vidraria excluída com sucesso'], 200);
    }
}
