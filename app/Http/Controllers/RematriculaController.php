<?php

namespace App\Http\Controllers;

use App\Models\PeriodoLetivo;
use App\Support\Exportador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RematriculaController extends Controller
{
    /** Prioridade para escolher um único status do próximo período por aluno. */
    protected array $prioridade = ['ATIVA', 'AGUARDANDO', 'APROVADO', 'APROVADO_PARCIALMENTE', 'REMANEJADA', 'REENQUADRADA'];

    public function index(Request $request)
    {
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')
            ->get(['id_periodo_letivo', 'descricao', 'org_descricao', 'ano', 'semestre']);

        $idAnterior = $request->integer('anterior') ?: null;
        $idProximo = $request->integer('proximo') ?: null;

        $porCurso = $porTurma = [];
        $statusCols = [];
        $totCurso = $totTurma = [];
        $resumo = null;
        $pAnterior = $pProximo = null;

        if ($idAnterior && $idProximo) {
            $pAnterior = $periodos->firstWhere('id_periodo_letivo', $idAnterior);
            $pProximo = $periodos->firstWhere('id_periodo_letivo', $idProximo);

            ['porCurso' => $porCurso, 'porTurma' => $porTurma, 'statusCols' => $statusCols,
                'totCurso' => $totCurso, 'totTurma' => $totTurma, 'resumo' => $resumo]
                = $this->computar($idAnterior, $idProximo);
        }

        return view('rematricula.index', compact(
            'periodos', 'idAnterior', 'idProximo', 'pAnterior', 'pProximo',
            'porCurso', 'porTurma', 'statusCols', 'totCurso', 'totTurma', 'resumo'
        ));
    }

    /**
     * Calcula os pivôs de rematrícula (por curso e por turma), colunas de
     * status, totais e o resumo/funil entre dois períodos.
     *
     * @return array{porCurso:array,porTurma:array,statusCols:list<string>,totCurso:array,totTurma:array,resumo:array}
     */
    protected function computar(int $idAnterior, int $idProximo): array
    {
        $porCurso = $porTurma = [];
        $totCurso = $totTurma = [];

        // Coorte: matrículas ATIVAS no período anterior.
        $coorte = DB::table('matriculas')
            ->where('id_periodo_letivo', $idAnterior)
            ->where('status', 'ATIVA')
            ->get(['id_aluno', 'unidade_fisica', 'id_curso_base', 'curso', 'id_turma', 'turma', 'id_curso_matriz']);

        // Possíveis formandos: aluno no último semestre da matriz.
        // período atual (da turma) >= total de semestres (da matriz).
        $turmaPeriodo = DB::table('turmas')->whereNotNull('periodo_numero')
            ->pluck('periodo_numero', 'id_turma');
        $matrizTotal = DB::table('matrizes')->whereNotNull('total_semestres')
            ->pluck('total_semestres', 'id_curso_matriz');

        // Matrículas do próximo período (com curso/turma p/ os novos alunos).
        $proxRows = DB::table('matriculas')
            ->where('id_periodo_letivo', $idProximo)
            ->get(['id_aluno', 'status', 'unidade_fisica', 'id_curso_base', 'curso', 'id_turma', 'turma']);

        // Status de cada aluno no próximo período (um por aluno, por prioridade).
        $proxPorAluno = [];
        foreach ($proxRows as $r) {
            $atual = $proxPorAluno[$r->id_aluno] ?? null;
            if ($atual === null || $this->rank($r->status) < $this->rank($atual)) {
                $proxPorAluno[$r->id_aluno] = $r->status;
            }
        }

        // Novos alunos: alunos ATIVA/AGUARDANDO no próximo período que NÃO
        // estavam ATIVOS no período anterior (ou seja, fora da coorte de
        // rematrícula). Inclui ingressantes e também retornantes.
        $coorteAlunos = collect($coorte)->pluck('id_aluno')->flip();

        // Inadimplência: títulos vencidos em aberto do período anterior E de
        // períodos anteriores a ele (dívida acumulada). O corte é cronológico,
        // pelos períodos cuja data de início é <= a do período anterior.
        $periodosInad = $this->periodosAteAnterior($idAnterior);
        $valorInadPorAluno = DB::table('titulos_abertos')
            ->whereNotNull('id_aluno')
            ->whereIn('id_periodo_letivo', $periodosInad)
            ->groupBy('id_aluno')
            ->selectRaw('id_aluno, sum(valor) as v')
            ->pluck('v', 'id_aluno');

        $NAO = 'NÃO REMATRICULOU';
        $statusSet = [];

        $addPivot = function (array &$pivot, array &$tot, string $chave, array $meta, ?string $statusProx, bool $inad, float $valorInad, bool $formando) use (&$statusSet) {
            if (! isset($pivot[$chave])) {
                $pivot[$chave] = $meta + ['status' => [], 'total' => 0, 'formandos' => 0, 'base_remat' => 0, 'adimpl' => 0, 'inadimpl' => 0, 'valor_inad' => 0.0, 'novos' => 0];
            }
            $pivot[$chave]['total']++;
            $pivot[$chave][$inad ? 'inadimpl' : 'adimpl']++;
            $pivot[$chave]['valor_inad'] += $valorInad;
            $tot['total'] = ($tot['total'] ?? 0) + 1;
            $tot[$inad ? 'inadimpl' : 'adimpl'] = ($tot[$inad ? 'inadimpl' : 'adimpl'] ?? 0) + 1;
            $tot['valor_inad'] = ($tot['valor_inad'] ?? 0) + $valorInad;

            if ($formando) {
                // Possível formando: fora da base de rematrícula.
                $pivot[$chave]['formandos']++;
                $tot['formandos'] = ($tot['formandos'] ?? 0) + 1;

                return;
            }

            $pivot[$chave]['status'][$statusProx] = ($pivot[$chave]['status'][$statusProx] ?? 0) + 1;
            $pivot[$chave]['base_remat']++;
            $tot[$statusProx] = ($tot[$statusProx] ?? 0) + 1;
            $tot['base_remat'] = ($tot['base_remat'] ?? 0) + 1;
            $statusSet[$statusProx] = true;
        };

        $inadAlunos = [];
        foreach ($coorte as $m) {
            $statusProx = $proxPorAluno[$m->id_aluno] ?? $NAO;
            $valorInad = (float) ($valorInadPorAluno[$m->id_aluno] ?? 0);
            $inad = $valorInad > 0;
            if ($inad) {
                $inadAlunos[$m->id_aluno] = true;
            }

            // Possível formando: turma no último semestre da matriz.
            $semAtual = $turmaPeriodo[$m->id_turma] ?? null;
            $semTotal = $matrizTotal[$m->id_curso_matriz] ?? null;
            $formando = $semAtual !== null && $semTotal !== null && $semAtual >= $semTotal;

            $unidade = $m->unidade_fisica ?: '(sem unidade)';

            $addPivot($porCurso, $totCurso,
                $unidade.'|'.($m->id_curso_base ?? 'x').'|'.$m->curso,
                ['unidade' => $unidade, 'id_curso_base' => $m->id_curso_base, 'curso' => $m->curso ?: '(sem curso)'],
                $statusProx, $inad, $valorInad, $formando);

            $addPivot($porTurma, $totTurma,
                $unidade.'|'.($m->id_turma ?? 'x'),
                ['unidade' => $unidade, 'id_turma' => $m->id_turma, 'turma' => $m->turma ?: '(sem turma)', 'curso' => $m->curso],
                $statusProx, $inad, $valorInad, $formando);
        }

        // Novos alunos: matrículas ATIVA/AGUARDANDO no próximo período de alunos
        // fora da coorte (não ATIVOS no anterior). Entram numa coluna própria,
        // por curso/turma do próximo período — fora dos status de rematrícula.
        $addNovo = function (array &$pivot, array &$tot, string $chave, array $meta) {
            if (! isset($pivot[$chave])) {
                $pivot[$chave] = $meta + ['status' => [], 'total' => 0, 'formandos' => 0, 'base_remat' => 0, 'adimpl' => 0, 'inadimpl' => 0, 'valor_inad' => 0.0, 'novos' => 0];
            }
            $pivot[$chave]['novos']++;
            $tot['novos'] = ($tot['novos'] ?? 0) + 1;
        };

        foreach ($proxRows as $r) {
            if (! in_array($r->status, ['ATIVA', 'AGUARDANDO'], true)) {
                continue;
            }
            if ($coorteAlunos->has($r->id_aluno)) {
                continue; // estava ATIVO no anterior (coorte) — é rematrícula, não novo
            }

            $unidade = $r->unidade_fisica ?: '(sem unidade)';

            $addNovo($porCurso, $totCurso,
                $unidade.'|'.($r->id_curso_base ?? 'x').'|'.$r->curso,
                ['unidade' => $unidade, 'id_curso_base' => $r->id_curso_base, 'curso' => $r->curso ?: '(sem curso)']);

            $addNovo($porTurma, $totTurma,
                $unidade.'|'.($r->id_turma ?? 'x'),
                ['unidade' => $unidade, 'id_turma' => $r->id_turma, 'turma' => $r->turma ?: '(sem turma)', 'curso' => $r->curso]);
        }

        // Ordena colunas de status: prioridade conhecida, depois demais, NÃO REMATRICULOU por último.
        $statusCols = $this->ordenarColunas(array_keys($statusSet), $NAO);

        // Ordena as linhas por Unidade → Curso [→ Turma].
        usort($porCurso, fn ($a, $b) => strcasecmp($a['unidade'], $b['unidade'])
            ?: strcasecmp($a['curso'] ?? '', $b['curso'] ?? ''));
        usort($porTurma, fn ($a, $b) => strcasecmp($a['unidade'], $b['unidade'])
            ?: strcasecmp($a['curso'] ?? '', $b['curso'] ?? '')
            ?: strcasecmp($a['turma'] ?? '', $b['turma'] ?? ''));

        // Resumo geral (funil). A base de rematrícula exclui os possíveis formandos.
        $ativosAnt = count($coorte);
        $formandos = $totCurso['formandos'] ?? 0;
        $baseRemat = $totCurso['base_remat'] ?? 0;
        $rematriculados = $baseRemat - ($totCurso[$NAO] ?? 0);
        $resumo = [
            'ativos_anterior' => $ativosAnt,
            'formandos' => $formandos,
            'base_remat' => $baseRemat,
            'rematriculados' => $rematriculados,
            'nao_rematriculou' => $totCurso[$NAO] ?? 0,
            'taxa' => $baseRemat ? round($rematriculados / $baseRemat * 100, 1) : 0,
            'ativos_proximo' => $totCurso['ATIVA'] ?? 0,
            'adimplentes' => $totCurso['adimpl'] ?? 0,
            'inadimplentes' => $totCurso['inadimpl'] ?? 0,
            'alunos_inadimplentes' => count($inadAlunos),
            'valor_inadimplente' => $totCurso['valor_inad'] ?? 0,
            'novos_alunos' => $totCurso['novos'] ?? 0,
        ];

        return compact('porCurso', 'porTurma', 'statusCols', 'totCurso', 'totTurma', 'resumo');
    }

    /**
     * IDs dos períodos letivos cronologicamente <= ao período anterior informado.
     * Usa data_inicio como referência (fallback: ano/semestre).
     *
     * @return list<int>
     */
    protected function periodosAteAnterior(int $idAnterior): array
    {
        $ref = DB::table('periodos_letivos')
            ->where('id_periodo_letivo', $idAnterior)
            ->first(['data_inicio', 'ano', 'semestre']);

        if (! $ref) {
            return [$idAnterior];
        }

        $q = DB::table('periodos_letivos');

        if (! empty($ref->data_inicio)) {
            $q->where('data_inicio', '<=', $ref->data_inicio);
        } else {
            // Sem data: compara pelo par (ano, semestre).
            $q->where(function ($w) use ($ref) {
                $w->where('ano', '<', $ref->ano)
                    ->orWhere(function ($w2) use ($ref) {
                        $w2->where('ano', $ref->ano)->where('semestre', '<=', $ref->semestre ?? 0);
                    });
            });
        }

        $ids = $q->pluck('id_periodo_letivo')->map(fn ($v) => (int) $v)->all();

        // Garante que o próprio período anterior esteja incluído.
        if (! in_array($idAnterior, $ids, true)) {
            $ids[] = $idAnterior;
        }

        return $ids;
    }

    /** Valida os períodos escolhidos e devolve o par [anterior, proximo] ou null. */
    protected function periodosSelecionados(Request $request): ?array
    {
        $idAnterior = $request->integer('anterior') ?: null;
        $idProximo = $request->integer('proximo') ?: null;

        return ($idAnterior && $idProximo) ? [$idAnterior, $idProximo] : null;
    }

    /** Colunas identificadoras (dimensões) de cada visão. */
    protected function dimensoesRematricula(string $visao): array
    {
        return $visao === 'turma'
            ? [['label' => 'Unidade', 'alias' => 'unidade'], ['label' => 'Curso', 'alias' => 'curso'], ['label' => 'Turma', 'alias' => 'turma']]
            : [['label' => 'Unidade', 'alias' => 'unidade'], ['label' => 'Curso', 'alias' => 'curso']];
    }

    /**
     * Exporta uma das duas visões (curso | turma) em excel|pdf, respeitando os
     * períodos selecionados.
     */
    public function exportar(Request $request, string $visao, string $formato)
    {
        $par = $this->periodosSelecionados($request);
        if (! $par) {
            return back()->with('erro', 'Selecione os dois períodos antes de exportar.');
        }

        $periodos = PeriodoLetivo::get(['id_periodo_letivo', 'descricao', 'org_descricao']);
        $pAnterior = $periodos->firstWhere('id_periodo_letivo', $par[0]);
        $pProximo = $periodos->firstWhere('id_periodo_letivo', $par[1]);

        $dados = $this->computar($par[0], $par[1]);
        $statusCols = $dados['statusCols'];
        $dimensoes = $this->dimensoesRematricula($visao);
        $rows = $visao === 'turma' ? $dados['porTurma'] : $dados['porCurso'];
        $titulo = $visao === 'turma' ? 'Rematrícula por Turma' : 'Rematrícula por Curso';

        if ($formato === 'pdf') {
            return view('rematricula.export_pdf', compact(
                'titulo', 'dimensoes', 'rows', 'statusCols', 'pAnterior', 'pProximo'
            ) + ['resumo' => $dados['resumo']]);
        }

        $colunas = array_merge(
            array_column($dimensoes, 'label'),
            $statusCols,
            ['Novos alunos', 'Poss. formandos', 'Base rematrícula', 'Adimplentes', 'Inadimplentes', 'Valor inadimplente', 'Total']
        );

        $linhas = function () use ($rows, $dimensoes, $statusCols) {
            foreach ($rows as $r) {
                $cols = [];
                foreach ($dimensoes as $d) {
                    $cols[] = $r[$d['alias']] ?? '';
                }
                foreach ($statusCols as $s) {
                    $cols[] = $r['status'][$s] ?? 0;
                }
                $cols[] = $r['novos'] ?? 0;
                $cols[] = $r['formandos'] ?? 0;
                $cols[] = $r['base_remat'] ?? 0;
                $cols[] = $r['adimpl'] ?? 0;
                $cols[] = $r['inadimpl'] ?? 0;
                $cols[] = number_format((float) ($r['valor_inad'] ?? 0), 2, ',', '.');
                $cols[] = $r['total'] ?? 0;
                yield $cols;
            }
        };

        return Exportador::csv('rematricula_'.$visao, $colunas, $linhas());
    }

    protected function rank(?string $status): int
    {
        $i = array_search($status, $this->prioridade, true);

        return $i === false ? 99 : $i;
    }

    protected function ordenarColunas(array $status, string $nao): array
    {
        $status = array_filter($status, fn ($s) => $s !== $nao);
        usort($status, function ($a, $b) {
            $ra = $this->rank($a);
            $rb = $this->rank($b);

            return $ra === $rb ? strcmp($a, $b) : $ra <=> $rb;
        });
        $status[] = $nao;

        return $status;
    }
}
