<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\LocalizacaoController;
use App\Http\Controllers\LaboratorioController;
use App\Http\Controllers\HistoricoDescarteController;
use App\Http\Controllers\UsuarioController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::apiResource('itens', ItemController::class);
Route::apiResource('movimentacoes', MovimentacaoController::class);
Route::apiResource('fornecedores', FornecedorController::class);
Route::apiResource('produtos', ProdutoController::class);
Route::apiResource('estoques', EstoqueController::class);
Route::apiResource('lotes', LoteController::class);
Route::apiResource('localizacoes', LocalizacaoController::class);
Route::apiResource('laboratorios', LaboratorioController::class);
Route::apiResource('descartes', HistoricoDescarteController::class);
Route::apiResource('usuarios', UsuarioController::class);