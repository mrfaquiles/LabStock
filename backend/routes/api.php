<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfiguracaoController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RelatorioController;
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

// Recursos de estoque: leitura liberada a todos os perfis, escrita apenas para admin/técnico
$recursosEstoque = [
    'unidademedidas' => UnidadeMedidaController::class,
    'entradavidrarias' => entradaVidrariaController::class,
    'entradareagentes' => entradaReagenteController::class,
    'saidareagentes' => saidaReagenteController::class,
    'entradaequipamentos' => entradaEquipamentoController::class,
    'saidaequipamentos' => saidaEquipamentoController::class,
    'laboratorios' => laboratorioController::class,
    'vidrarias' => vidrariaController::class,
    'reagentes' => reagenteController::class,
    'equipamentos' => equipamentoController::class,
];

// Rota pública
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () use ($recursosEstoque) {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('relatorios/estoque', [RelatorioController::class, 'estoque']);
    Route::get('relatorios/gastos', [RelatorioController::class, 'gastos']);
    Route::get('configuracoes', [ConfiguracaoController::class, 'index']);

    // Todos os perfis (admin, tecnico, consulta)
    foreach ($recursosEstoque as $recurso => $controller) {
        Route::apiResource($recurso, $controller)->only(['index', 'show']);
    }

    // Cadastro e movimentação de estoque
    Route::middleware('perfil:admin,tecnico')->group(function () use ($recursosEstoque) {
        foreach ($recursosEstoque as $recurso => $controller) {
            Route::apiResource($recurso, $controller)->except(['index', 'show']);
        }
    });

    // Baixa de vidraria: qualquer perfil solicita (e cancela a própria pendente); o admin decide
    Route::apiResource('saidavidrarias', saidaVidrariaController::class)->except(['update']);

    // Gestão de usuários e aprovação de baixas de vidraria
    Route::middleware('perfil:admin')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
        Route::put('configuracoes', [ConfiguracaoController::class, 'update']);
        Route::post('saidavidrarias/{id}/aprovar', [saidaVidrariaController::class, 'aprovar']);
        Route::post('saidavidrarias/{id}/recusar', [saidaVidrariaController::class, 'recusar']);
    });
});
