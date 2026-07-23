<?php

namespace App\Http\Controllers;

use App\Models\ApiParametro;
use App\Models\IngestaoLog;
use App\Models\PeriodoLetivo;
use App\Services\Jacad\JacadClient;
use App\Services\Jacad\MatriculaIngestService;
use Illuminate\Http\Request;

class IngestaoController extends Controller
{
    public function index()
    {
        $logs = IngestaoLog::orderByDesc('id')->paginate(20);
        $anos = PeriodoLetivo::whereNotNull('ano')->distinct()->orderByDesc('ano')->pluck('ano');
        $conexaoOk = ApiParametro::porNome('jacad') !== null;

        return view('ingestao.index', compact('logs', 'anos', 'conexaoOk'));
    }

    /**
     * Dispara a sincronização de matrículas (todos os anos ou um ano).
     * Executada de forma síncrona; para grandes volumes use o command
     * `php artisan jacad:sync-matriculas`.
     */
    public function sincronizar(Request $request)
    {
        $request->validate(['ano' => ['nullable', 'integer']]);
        @set_time_limit(0);

        try {
            $ano = $request->filled('ano') ? (int) $request->ano : null;
            $service = new MatriculaIngestService(new JacadClient());
            $reg = $service->sincronizarTudo($ano);

            return back()->with('sucesso',
                "Sincronização concluída. {$reg->total_registros} matrículas em {$reg->total_paginas} páginas.");
        } catch (\Throwable $e) {
            return back()->with('erro', 'Falha na sincronização: '.$e->getMessage());
        }
    }

    /**
     * Dispara a sincronização de cidades + perfis (endereço do aluno), que
     * alimenta o painel de Demografia.
     */
    public function sincronizarDemografia()
    {
        @set_time_limit(0);

        try {
            $reg = (new MatriculaIngestService(new JacadClient()))->sincronizarDemografia();

            return back()->with('sucesso', "Dados de endereço atualizados. {$reg->mensagem}.");
        } catch (\Throwable $e) {
            return back()->with('erro', 'Falha ao sincronizar os perfis: '.$e->getMessage());
        }
    }

    /** Testa a conexão com o JACAD. */
    public function testar()
    {
        $r = (new JacadClient())->testarConexao();

        return $r['ok']
            ? back()->with('sucesso', 'Conexão com o JACAD OK.')
            : back()->with('erro', "Falha na conexão (HTTP {$r['status']}): ".$r['mensagem']);
    }
}
