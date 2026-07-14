<?php

namespace App\Http\Controllers;

use App\Models\IngestaoLog;
use App\Models\Matricula;
use App\Models\PeriodoLetivo;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalMatriculas = Matricula::count();
        $totalPeriodos = PeriodoLetivo::count();
        $totalAnos = PeriodoLetivo::whereNotNull('ano')->distinct()->count('ano');
        $ultimaSync = IngestaoLog::latest('id')->first();

        $porStatus = Matricula::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->orderByDesc('total')->get();

        $porAno = Matricula::query()
            ->join('periodos_letivos', 'matriculas.id_periodo_letivo', '=', 'periodos_letivos.id_periodo_letivo')
            ->select('periodos_letivos.ano', DB::raw('count(*) as total'))
            ->whereNotNull('periodos_letivos.ano')
            ->groupBy('periodos_letivos.ano')
            ->orderBy('periodos_letivos.ano', 'desc')
            ->get();

        // Granularidade por período letivo (1º/2º semestre e especiais separados).
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
            'ultimaSync', 'porStatus', 'porAno', 'porPeriodo'
        ));
    }
}
