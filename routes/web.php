<?php

use App\Http\Controllers\ApiParametroController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CursoBaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngestaoController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\PeriodoLetivoController;
use App\Http\Controllers\RematriculaController;
use App\Http\Controllers\UsuarioController;
use App\Support\Permissions;
use Illuminate\Support\Facades\Route;

// Autenticação
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Acadêmico — Matrículas
    Route::middleware('can:'.Permissions::MATRICULAS_VER)->group(function () {
        Route::get('/matriculas', [MatriculaController::class, 'index'])->name('matriculas.index');
        Route::get('/matriculas/exportar/{visao}/{formato}', [MatriculaController::class, 'exportar'])
            ->whereIn('visao', ['lista', 'curso', 'turma'])
            ->whereIn('formato', ['excel', 'pdf'])
            ->name('matriculas.exportar');
        Route::get('/matriculas/{matricula}', [MatriculaController::class, 'show'])->name('matriculas.show');
        Route::get('/cursos', [CursoBaseController::class, 'index'])->name('cursos.index');
    });

    // Acadêmico — Rematrícula
    Route::middleware('can:'.Permissions::REMATRICULA_VER)->group(function () {
        Route::get('/rematricula', [RematriculaController::class, 'index'])->name('rematricula.index');
        Route::get('/rematricula/exportar/{visao}/{formato}', [RematriculaController::class, 'exportar'])
            ->whereIn('visao', ['curso', 'turma'])
            ->whereIn('formato', ['excel', 'pdf'])
            ->name('rematricula.exportar');
    });

    Route::get('/periodos-letivos', [PeriodoLetivoController::class, 'index'])->name('periodos.index');
    Route::post('/periodos-letivos/sincronizar', [PeriodoLetivoController::class, 'sincronizar'])
        ->middleware('can:'.Permissions::DADOS_SINCRONIZAR)->name('periodos.sincronizar');

    // Integração — Sincronização
    Route::middleware('can:'.Permissions::DADOS_SINCRONIZAR)->group(function () {
        Route::get('/ingestao', [IngestaoController::class, 'index'])->name('ingestao.index');
        Route::post('/ingestao/sincronizar', [IngestaoController::class, 'sincronizar'])->name('ingestao.sincronizar');
        Route::post('/ingestao/testar', [IngestaoController::class, 'testar'])->name('ingestao.testar');
    });

    // Parâmetros de API
    Route::middleware('can:'.Permissions::PARAMETROS_GERENCIAR)->group(function () {
        Route::get('/parametros', [ApiParametroController::class, 'index'])->name('parametros.index');
        Route::get('/parametros/{parametro}/editar', [ApiParametroController::class, 'edit'])->name('parametros.edit');
        Route::put('/parametros/{parametro}', [ApiParametroController::class, 'update'])->name('parametros.update');
        Route::post('/parametros/{parametro}/testar', [ApiParametroController::class, 'testar'])->name('parametros.testar');
    });

    // Administração — Usuários e permissões
    Route::middleware('can:'.Permissions::USUARIOS_GERENCIAR)->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    });

    // Administração — Logs de acesso (login/logout)
    Route::middleware('can:'.Permissions::LOGS_ACESSO_VER)->group(function () {
        Route::get('/logs-acesso', [LoginLogController::class, 'index'])->name('logs-acesso.index');
    });
});
