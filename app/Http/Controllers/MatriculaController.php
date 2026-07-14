<?php

namespace App\Http\Controllers;

use App\Models\CursoBase;
use App\Models\Matricula;
use App\Models\PeriodoLetivo;
use App\Support\Exportador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatriculaController extends Controller
{
    public function index(Request $request)
    {
        // Filtros de contexto (tudo, menos o status) — aplicados aos cards, à lista e ao sintético.
        $base = $this->baseFiltrada($request);

        // Totalizadores por status (respeita o contexto; ignora o filtro de status).
        $porStatus = (clone $base)
            ->select('matriculas.status', DB::raw('count(*) as total'))
            ->groupBy('matriculas.status')
            ->orderByDesc('total')
            ->pluck('total', 'matriculas.status');

        $totalContexto = (clone $base)->count();

        // Lista paginada: aplica também o filtro de status.
        $lista = (clone $base)->select('matriculas.*', 'periodos_letivos.ano as pl_ano');
        if ($request->filled('status')) {
            $lista->where('matriculas.status', $request->status);
        }
        $matriculas = $lista->orderByDesc('matriculas.id_matricula')
            ->paginate(25)->withQueryString();

        // Colunas de status (ordenadas por volume) — comuns aos sintéticos.
        $statusColunas = $porStatus->keys()->filter()->values()->all();

        // Sintético por curso × status (respeita o contexto).
        [$sinteticoCurso, $sinteticoCursoTotais] = $this->montarSintetico(
            clone $base, $statusColunas, 'matriculas.id_curso_base', 'matriculas.curso'
        );

        // Sintético por turma × status (respeita o contexto).
        [$sintetico, $sinteticoTotais] = $this->montarSintetico(
            clone $base, $statusColunas, 'matriculas.id_turma', 'matriculas.turma', 'matriculas.curso'
        );

        // Opções dos filtros.
        $anos = PeriodoLetivo::whereNotNull('ano')->distinct()->orderByDesc('ano')->pluck('ano');
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')
            ->get(['id_periodo_letivo', 'descricao', 'org_descricao', 'ano']);
        $cursos = CursoBase::orderBy('nome_impressao')->get(['id_curso_base', 'nome_impressao']);
        // Turmas relevantes ao contexto atual (só as que têm matrícula no filtro).
        $turmas = (clone $base)
            ->whereNotNull('matriculas.id_turma')
            ->select('matriculas.id_turma', 'matriculas.turma')
            ->distinct()->orderBy('matriculas.turma')->limit(2000)
            ->get();

        return view('matriculas.index', compact(
            'matriculas', 'anos', 'periodos', 'cursos', 'turmas',
            'porStatus', 'totalContexto', 'statusColunas',
            'sinteticoCurso', 'sinteticoCursoTotais',
            'sintetico', 'sinteticoTotais'
        ));
    }

    /** Monta a query base com os filtros de contexto aplicados. */
    protected function baseFiltrada(Request $request): Builder
    {
        $base = Matricula::query()
            ->leftJoin('periodos_letivos', 'matriculas.id_periodo_letivo', '=', 'periodos_letivos.id_periodo_letivo');

        $this->aplicarFiltrosContexto($base, $request);

        return $base;
    }

    /** Aplica os filtros de contexto (busca, período, ano, curso, turma) — exceto status. */
    protected function aplicarFiltrosContexto(Builder $query, Request $request): void
    {
        if ($request->filled('busca')) {
            $b = trim($request->busca);
            $query->where(function ($q) use ($b) {
                $q->where('matriculas.aluno', 'ilike', "%{$b}%")
                    ->orWhere('matriculas.ra', 'ilike', "%{$b}%")
                    ->orWhere('matriculas.aluno_email', 'ilike', "%{$b}%");
            });
        }
        if ($request->filled('periodo')) {
            $query->where('matriculas.id_periodo_letivo', $request->periodo);
        }
        if ($request->filled('ano')) {
            $query->where('periodos_letivos.ano', $request->ano);
        }
        if ($request->filled('curso')) {
            $query->where('matriculas.id_curso_base', $request->curso);
        }
        if ($request->filled('turma')) {
            $query->where('matriculas.id_turma', $request->turma);
        }
    }

    /**
     * Monta um sintético (pivot) de matrículas agrupado por uma dimensão,
     * com os status em colunas + total.
     *
     * @param  string  $colId  coluna de id do agrupamento (ex.: matriculas.id_turma)
     * @param  string  $colLabel  coluna de rótulo (ex.: matriculas.turma)
     * @param  string|null  $colExtra  coluna adicional a exibir (ex.: matriculas.curso)
     * @return array{0: array, 1: array} [linhas, totais]
     */
    protected function montarSintetico(Builder $base, array $statusColunas, string $colId, string $colLabel, ?string $colExtra = null): array
    {
        $selects = [$colId, $colLabel, 'matriculas.status', DB::raw('count(*) as total')];
        $groups = [$colId, $colLabel, 'matriculas.status'];
        if ($colExtra) {
            array_splice($selects, 2, 0, [$colExtra]);
            $groups[] = $colExtra;
        }

        $idAlias = last(explode('.', $colId));
        $labelAlias = last(explode('.', $colLabel));
        $extraAlias = $colExtra ? last(explode('.', $colExtra)) : null;

        $linhas = $base->select($selects)->groupBy($groups)->get();

        $sintetico = [];
        $totais = ['total' => 0];
        foreach ($statusColunas as $s) {
            $totais[$s] = 0;
        }

        foreach ($linhas as $l) {
            $chave = $l->{$idAlias} ?? 'sem';
            if (! isset($sintetico[$chave])) {
                $sintetico[$chave] = [
                    'id' => $l->{$idAlias},
                    'label' => $l->{$labelAlias} ?: '(não informado)',
                    'extra' => $extraAlias ? $l->{$extraAlias} : null,
                    'status' => array_fill_keys($statusColunas, 0),
                    'total' => 0,
                ];
            }
            $sintetico[$chave]['status'][$l->status] = ($sintetico[$chave]['status'][$l->status] ?? 0) + $l->total;
            $sintetico[$chave]['total'] += $l->total;
            $totais['total'] += $l->total;
            if (isset($totais[$l->status])) {
                $totais[$l->status] += $l->total;
            }
        }

        usort($sintetico, fn ($a, $b) => $b['total'] <=> $a['total']);
        $sintetico = array_slice($sintetico, 0, 300);

        return [$sintetico, $totais];
    }

    public function show(Matricula $matricula)
    {
        return view('matriculas.show', compact('matricula'));
    }

    /** Exporta a lista detalhada (respeitando os filtros) em CSV/Excel. */
    public function exportarExcel(Request $request)
    {
        $lista = $this->baseFiltrada($request)
            ->select('matriculas.*', 'periodos_letivos.ano as pl_ano');
        if ($request->filled('status')) {
            $lista->where('matriculas.status', $request->status);
        }

        $colunas = ['RA', 'Aluno', 'E-mail', 'Curso', 'Turma', 'Período/Ano', 'Status'];

        $linhas = function () use ($lista) {
            foreach ($lista->orderBy('matriculas.aluno')->cursor() as $m) {
                yield [
                    $m->ra,
                    $m->aluno,
                    $m->aluno_email,
                    $m->curso,
                    $m->turma,
                    $m->pl_ano,
                    $m->status,
                ];
            }
        };

        return Exportador::csv('matriculas', $colunas, $linhas());
    }

    /** Gera o relatório sintético (por status/curso/turma) pronto para impressão em PDF. */
    public function exportarPdf(Request $request)
    {
        $base = $this->baseFiltrada($request);

        $porStatus = (clone $base)
            ->select('matriculas.status', DB::raw('count(*) as total'))
            ->groupBy('matriculas.status')->orderByDesc('total')
            ->pluck('total', 'matriculas.status');

        $totalContexto = (clone $base)->count();
        $statusColunas = $porStatus->keys()->filter()->values()->all();

        [$sinteticoCurso, $sinteticoCursoTotais] = $this->montarSintetico(
            clone $base, $statusColunas, 'matriculas.id_curso_base', 'matriculas.curso'
        );
        [$sintetico, $sinteticoTotais] = $this->montarSintetico(
            clone $base, $statusColunas, 'matriculas.id_turma', 'matriculas.turma', 'matriculas.curso'
        );

        return view('matriculas.export_pdf', compact(
            'porStatus', 'totalContexto', 'statusColunas',
            'sinteticoCurso', 'sinteticoCursoTotais', 'sintetico', 'sinteticoTotais'
        ));
    }
}
