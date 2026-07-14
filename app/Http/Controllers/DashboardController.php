<?php

namespace App\Http\Controllers;

use App\Models\IngestaoLog;
use App\Models\Matricula;
use App\Models\PeriodoLetivo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')
            ->get(['id_periodo_letivo', 'descricao', 'org_descricao', 'ano', 'semestre']);

        // Período selecionado: por padrão o mais recente (os números só fazem
        // sentido período a período). "todos" mostra o agregado geral.
        $periodoSel = $request->input('periodo');
        if ($periodoSel === null || $periodoSel === '') {
            $periodoSel = optional($periodos->first())->id_periodo_letivo;
        }
        $filtrar = $periodoSel !== 'todos' && $periodoSel !== null;
        $periodoAtual = $filtrar ? $periodos->firstWhere('id_periodo_letivo', (int) $periodoSel) : null;

        // Query base de matrículas já escopada ao período (quando aplicável).
        $scoped = fn (): Builder => Matricula::query()
            ->when($filtrar, fn ($q) => $q->where('id_periodo_letivo', $periodoSel));

        $totalMatriculas = $scoped()->count();
        $totalPeriodos = PeriodoLetivo::count();
        $totalAnos = PeriodoLetivo::whereNotNull('ano')->distinct()->count('ano');
        $ultimaSync = IngestaoLog::latest('id')->first();

        $porStatus = $scoped()->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->orderByDesc('total')->get();

        // Visão geral por período letivo (sempre todos — serve de panorama e
        // de navegação; o período selecionado é destacado).
        $porPeriodo = Matricula::query()
            ->join('periodos_letivos', 'matriculas.id_periodo_letivo', '=', 'periodos_letivos.id_periodo_letivo')
            ->select(
                'periodos_letivos.id_periodo_letivo',
                'periodos_letivos.descricao',
                'periodos_letivos.org_descricao',
                'periodos_letivos.ano',
                'periodos_letivos.semestre',
                DB::raw('count(*) as total')
            )
            ->groupBy('periodos_letivos.id_periodo_letivo', 'periodos_letivos.descricao', 'periodos_letivos.org_descricao', 'periodos_letivos.ano', 'periodos_letivos.semestre')
            ->orderByDesc('periodos_letivos.ano')
            ->orderByDesc('periodos_letivos.semestre')
            ->orderByDesc('total')
            ->limit(16)
            ->get();

        return view('dashboard', compact(
            'totalMatriculas', 'totalPeriodos', 'totalAnos',
            'ultimaSync', 'porStatus', 'porPeriodo',
            'periodos', 'periodoSel', 'periodoAtual', 'filtrar'
        ));
    }
}
