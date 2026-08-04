<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoteReagenteController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\UnidadeMedidaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas completas do LabStock
Route::apiResource('itens', ItemController::class);
Route::apiResource('lotes-reagentes', LoteReagenteController::class);
Route::apiResource('movimentacoes', MovimentacaoController::class);
Route::apiResource('unidades-medida', UnidadeMedidaController::class);