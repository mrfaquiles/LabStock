<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\ProdutoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/itens', [ItemController::class, 'index']);
Route::post('/itens', [ItemController::class, 'store']);
Route::apiResource('fornecedores', FornecedorController::class);
Route::apiResource('produtos', ProdutoController::class);