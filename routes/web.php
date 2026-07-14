<?php

use App\Http\Controllers\ApiParametroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngestaoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\PeriodoLetivoController;
use App\Http\Controllers\RematriculaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Acadêmico
Route::get('/matriculas', [MatriculaController::class, 'index'])->name('matriculas.index');
Route::get('/matriculas/{matricula}', [MatriculaController::class, 'show'])->name('matriculas.show');

Route::get('/rematricula', [RematriculaController::class, 'index'])->name('rematricula.index');

Route::get('/periodos-letivos', [PeriodoLetivoController::class, 'index'])->name('periodos.index');
Route::post('/periodos-letivos/sincronizar', [PeriodoLetivoController::class, 'sincronizar'])->name('periodos.sincronizar');

// Integração
Route::get('/ingestao', [IngestaoController::class, 'index'])->name('ingestao.index');
Route::post('/ingestao/sincronizar', [IngestaoController::class, 'sincronizar'])->name('ingestao.sincronizar');
Route::post('/ingestao/testar', [IngestaoController::class, 'testar'])->name('ingestao.testar');

// Parâmetros de API
Route::get('/parametros', [ApiParametroController::class, 'index'])->name('parametros.index');
Route::get('/parametros/{parametro}/editar', [ApiParametroController::class, 'edit'])->name('parametros.edit');
Route::put('/parametros/{parametro}', [ApiParametroController::class, 'update'])->name('parametros.update');
Route::post('/parametros/{parametro}/testar', [ApiParametroController::class, 'testar'])->name('parametros.testar');
