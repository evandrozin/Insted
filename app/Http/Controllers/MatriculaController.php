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
        $lista = (clone $base)
            ->select('matriculas.*', 'periodos_letivos.ano as pl_ano')
            ->selectRaw($this->selectInadimplente());
        if ($request->filled('status')) {
            $lista->where('matriculas.status', $request->status);
        }
        $matriculas = $lista
            ->orderBy('matriculas.unidade_fisica')
            ->orderBy('matriculas.curso')
            ->orderBy('matriculas.turma')
            ->orderBy('matriculas.aluno')
            ->paginate(25)->withQueryString();

        // Colunas de status (ordenadas por volume) — comuns aos sintéticos.
        $statusColunas = $porStatus->keys()->filter()->values()->all();

        // Sintético por Unidade → Curso (respeita o contexto).
        $dimCurso = $this->dimensoesSintetico('curso');
        [$sinteticoCurso, $sinteticoCursoTotais] = $this->montarSintetico(clone $base, $statusColunas, $dimCurso);

        // Sintético por Unidade → Curso → Turma (respeita o contexto).
        $dimTurma = $this->dimensoesSintetico('turma');
        [$sintetico, $sinteticoTotais] = $this->montarSintetico(clone $base, $statusColunas, $dimTurma);

        // Opções dos filtros.
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')
            ->get(['id_periodo_letivo', 'descricao', 'org_descricao', 'ano']);
        $cursos = CursoBase::orderBy('nome_impressao')->get(['id_curso_base', 'nome_impressao', 'modalidade']);
        // Turmas relevantes ao contexto atual (só as que têm matrícula no filtro).
        $turmas = (clone $base)
            ->whereNotNull('matriculas.id_turma')
            ->select('matriculas.id_turma', 'matriculas.turma')
            ->distinct()->orderBy('matriculas.turma')->limit(2000)
            ->get();

        return view('matriculas.index', compact(
            'matriculas', 'periodos', 'cursos', 'turmas',
            'porStatus', 'totalContexto', 'statusColunas',
            'sinteticoCurso', 'sinteticoCursoTotais', 'dimCurso',
            'sintetico', 'sinteticoTotais', 'dimTurma'
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
        if ($request->filled('curso')) {
            $query->where('matriculas.id_curso_base', $request->curso);
        }
        if ($request->filled('turma')) {
            $query->where('matriculas.id_turma', $request->turma);
        }
        if ($request->filled('adimplencia')) {
            $existeTitulo = function ($q) {
                $q->select(DB::raw(1))
                    ->from('titulos_abertos as t')
                    ->whereColumn('t.id_matricula', 'matriculas.id_matricula');
            };
            if ($request->adimplencia === 'inadimplente') {
                $query->whereExists($existeTitulo);
            } elseif ($request->adimplencia === 'adimplente') {
                $query->whereNotExists($existeTitulo);
            }
        }
    }

    /**
     * Expressão SQL que marca a matrícula como inadimplente (1) quando há
     * título em aberto/vencido vinculado a ela em titulos_abertos.
     * (a tabela já contém apenas títulos ABERTO e vencidos).
     */
    protected function selectInadimplente(): string
    {
        return 'CASE WHEN EXISTS (SELECT 1 FROM titulos_abertos t WHERE t.id_matricula = matriculas.id_matricula) THEN 1 ELSE 0 END as inadimplente';
    }

    /**
     * Dimensões (colunas identificadoras) de cada sintético, na ordem exigida.
     * 'filtro' habilita o drill-down (link) para o filtro de matrículas.
     */
    protected function dimensoesSintetico(string $visao): array
    {
        $unidade = ['label' => 'Unidade', 'col' => 'matriculas.unidade_fisica', 'alias' => 'unidade'];
        $curso = ['label' => 'Curso', 'col' => 'matriculas.curso', 'alias' => 'curso', 'id_col' => 'matriculas.id_curso_base', 'filtro' => 'curso'];
        $turma = ['label' => 'Turma', 'col' => 'matriculas.turma', 'alias' => 'turma', 'id_col' => 'matriculas.id_turma', 'filtro' => 'turma'];

        return $visao === 'turma'
            ? [$unidade, ['label' => 'Curso', 'col' => 'matriculas.curso', 'alias' => 'curso'], $turma]
            : [$unidade, $curso];
    }

    /**
     * Monta um sintético (pivot) agrupado por uma lista ordenada de dimensões
     * (ex.: Unidade → Curso → Turma), com os status em colunas + total.
     * As linhas saem ordenadas pelas dimensões, na ordem informada.
     *
     * @return array{0: array, 1: array} [linhas, totais]
     */
    protected function montarSintetico(Builder $base, array $statusColunas, array $dimensoes): array
    {
        $selects = ['matriculas.status', DB::raw('count(*) as total')];
        $groups = ['matriculas.status'];
        foreach ($dimensoes as $d) {
            $selects[] = $d['col'].' as '.$d['alias'];
            $groups[] = $d['col'];
            if (! empty($d['id_col'])) {
                $selects[] = $d['id_col'].' as '.$d['alias'].'_id';
                $groups[] = $d['id_col'];
            }
        }

        $linhas = $base->select($selects)->groupBy($groups)->get();

        $sintetico = [];
        $totais = ['total' => 0];
        foreach ($statusColunas as $s) {
            $totais[$s] = 0;
        }

        foreach ($linhas as $l) {
            $chaveParts = $dims = $ids = [];
            foreach ($dimensoes as $d) {
                $val = $l->{$d['alias']};
                $chaveParts[] = (string) $val;
                $dims[$d['alias']] = $val ?: '(não informado)';
                if (! empty($d['id_col'])) {
                    $ids[$d['alias']] = $l->{$d['alias'].'_id'};
                }
            }
            $chave = implode('|', $chaveParts);

            if (! isset($sintetico[$chave])) {
                $sintetico[$chave] = [
                    'dims' => $dims,
                    'ids' => $ids,
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

        // Ordena pelas dimensões, na ordem (Unidade, Curso[, Turma]).
        $aliases = array_column($dimensoes, 'alias');
        usort($sintetico, function ($a, $b) use ($aliases) {
            foreach ($aliases as $al) {
                $cmp = strcasecmp((string) $a['dims'][$al], (string) $b['dims'][$al]);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return 0;
        });

        return [$sintetico, $totais];
    }

    /** Colunas de status (ordenadas por volume) de uma base. */
    protected function statusColunas(Builder $base): array
    {
        return (clone $base)
            ->select('matriculas.status', DB::raw('count(*) as total'))
            ->groupBy('matriculas.status')->orderByDesc('total')
            ->pluck('total', 'matriculas.status')
            ->keys()->filter()->values()->all();
    }

    public function show(Matricula $matricula)
    {
        return view('matriculas.show', compact('matricula'));
    }

    /**
     * Exporta uma das três visões (lista | curso | turma) em excel|pdf,
     * respeitando os filtros atuais.
     */
    public function exportar(Request $request, string $visao, string $formato)
    {
        return match ($visao) {
            'lista' => $this->exportarLista($request, $formato),
            'curso', 'turma' => $this->exportarSintetico($request, $visao, $formato),
            default => abort(404),
        };
    }

    /** Lista detalhada (Unidade, Curso, Turma, RA, Aluno, ...). */
    protected function exportarLista(Request $request, string $formato)
    {
        $lista = $this->baseFiltrada($request)
            ->select('matriculas.*')
            ->selectRaw($this->selectInadimplente())
            ->selectRaw('(SELECT t2.pagador_cpf FROM titulos_abertos t2 WHERE t2.id_matricula = matriculas.id_matricula LIMIT 1) as pagador_cpf');
        if ($request->filled('status')) {
            $lista->where('matriculas.status', $request->status);
        }
        $lista->orderBy('matriculas.unidade_fisica')
            ->orderBy('matriculas.curso')
            ->orderBy('matriculas.turma')
            ->orderBy('matriculas.aluno');

        $colunas = ['Unidade', 'Curso', 'Turma', 'RA', 'Aluno', 'CPF (pagador)', 'E-mail', 'Período', 'Status', 'Adimplência'];

        if ($formato === 'pdf') {
            return view('matriculas.export_lista_pdf', [
                'colunas' => $colunas,
                'rows' => $lista->limit(5000)->get(),
            ]);
        }

        $linhas = function () use ($lista) {
            foreach ($lista->cursor() as $m) {
                yield [$m->unidade_fisica, $m->curso, $m->turma, $m->ra, $m->aluno, $m->pagador_cpf, $m->aluno_email, $m->periodo_letivo, $m->status, $m->inadimplente ? 'Inadimplente' : 'Adimplente'];
            }
        };

        return Exportador::csv('matriculas', $colunas, $linhas());
    }

    /** Sintético por Unidade→Curso ou Unidade→Curso→Turma. */
    protected function exportarSintetico(Request $request, string $visao, string $formato)
    {
        $base = $this->baseFiltrada($request);
        $statusColunas = $this->statusColunas($base);
        $dimensoes = $this->dimensoesSintetico($visao);
        [$rows, $totais] = $this->montarSintetico(clone $base, $statusColunas, $dimensoes);

        $titulo = $visao === 'turma' ? 'Sintético por Turma' : 'Sintético por Curso';

        if ($formato === 'pdf') {
            return view('matriculas.export_sintetico_pdf', compact('titulo', 'dimensoes', 'statusColunas', 'rows', 'totais'));
        }

        $colunas = array_merge(array_column($dimensoes, 'label'), $statusColunas, ['Total']);
        $linhas = function () use ($rows, $dimensoes, $statusColunas) {
            foreach ($rows as $r) {
                $col = [];
                foreach ($dimensoes as $d) {
                    $col[] = $r['dims'][$d['alias']] ?? '';
                }
                foreach ($statusColunas as $s) {
                    $col[] = $r['status'][$s] ?? 0;
                }
                $col[] = $r['total'] ?? 0;
                yield $col;
            }
        };

        return Exportador::csv('matriculas_'.$visao, $colunas, $linhas());
    }
}
