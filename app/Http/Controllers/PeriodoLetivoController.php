<?php

namespace App\Http\Controllers;

use App\Models\PeriodoLetivo;
use App\Services\Jacad\JacadClient;
use App\Services\Jacad\MatriculaIngestService;

class PeriodoLetivoController extends Controller
{
    public function index()
    {
        $periodos = PeriodoLetivo::withCount('matriculas')
            ->orderByDesc('ano')->orderByDesc('semestre')
            ->paginate(30);

        return view('periodos.index', compact('periodos'));
    }

    /** Atualiza somente a lista de períodos letivos a partir do JACAD. */
    public function sincronizar()
    {
        try {
            $service = new MatriculaIngestService(new JacadClient());
            $total = $service->sincronizarPeriodosLetivos();

            return back()->with('sucesso', "Períodos letivos atualizados: {$total}.");
        } catch (\Throwable $e) {
            return back()->with('erro', 'Falha ao sincronizar períodos: '.$e->getMessage());
        }
    }
}
