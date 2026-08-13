<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vidraria; // Certifique-se de que o Model existe

class VidrariaController extends Controller
{
    public function index()
    {
        return Vidraria::all();
    }

    public function store(Request $request)
    {
        $vidraria = Vidraria::create($request->all());
        return response()->json($vidraria, 201);
    }

    public function show(string $id)
    {
        return Vidraria::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $vidraria = Vidraria::findOrFail($id);
        $vidraria->update($request->all());
        return response()->json($vidraria, 200);
    }

    public function destroy(string $id)
    {
        Vidraria::destroy($id);
        return response()->json(null, 204);
    }
}