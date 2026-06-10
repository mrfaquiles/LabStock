<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        // Vai à base de dados, pega em todos os itens e devolve em formato JSON
        $itens = Item::all();
        return response()->json($itens);
    }
    
    public function store(Request $request)
    {
    return Item::create($request->all());
    }
}

