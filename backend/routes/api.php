<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\laboratorioController;
use App\Http\Controllers\Api\vidrariaController;
use App\Http\Controllers\Api\reagenteController;
use App\Http\Controllers\Api\equipamentoController;
use App\Http\Controllers\Api\entradaReagenteController;
use App\Http\Controllers\Api\saidaReagenteController;
use App\Http\Controllers\Api\entradaVidrariaController;
use App\Http\Controllers\Api\saidaVidrariaController;
use App\Http\Controllers\Api\entradaEquipamentoController;
use App\Http\Controllers\Api\saidaEquipamentoController;
use App\Http\Controllers\Api\UnidadeMedidaController;
use App\Http\Controllers\Api\UsuarioController;

Route::apiResource('unidademedidas', UnidadeMedidaController::class);
Route::apiResource('entradavidrarias', entradaVidrariaController::class);
Route::apiResource('saidavidrarias', saidaVidrariaController::class);
Route::apiResource('entradareagentes', entradaReagenteController::class);
Route::apiResource('saidareagentes', saidaReagenteController::class);
Route::apiResource('entradaequipamentos', entradaEquipamentoController::class);
Route::apiResource('saidaequipamentos', saidaEquipamentoController::class);
Route::apiResource('laboratorios', laboratorioController::class);
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('vidrarias', vidrariaController::class);
Route::apiResource('reagentes', reagenteController::class);
Route::apiResource('equipamentos', equipamentoController::class);