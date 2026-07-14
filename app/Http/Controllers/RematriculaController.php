<?php

namespace App\Http\Controllers;

use App\Models\PeriodoLetivo;
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

            // Coorte: matrículas ATIVAS no período anterior.
            $coorte = DB::table('matriculas')
                ->where('id_periodo_letivo', $idAnterior)
                ->where('status', 'ATIVA')
                ->get(['id_aluno', 'id_curso_base', 'curso', 'id_turma', 'turma', 'id_curso_matriz']);

            // Possíveis formandos: aluno no último semestre da matriz.
            // período atual (da turma) >= total de semestres (da matriz).
            $turmaPeriodo = DB::table('turmas')->whereNotNull('periodo_numero')
                ->pluck('periodo_numero', 'id_turma');
            $matrizTotal = DB::table('matrizes')->whereNotNull('total_semestres')
                ->pluck('total_semestres', 'id_curso_matriz');

            // Status de cada aluno no próximo período (um por aluno, por prioridade).
            $proxRows = DB::table('matriculas')
                ->where('id_periodo_letivo', $idProximo)
                ->get(['id_aluno', 'status']);

            $proxPorAluno = [];
            foreach ($proxRows as $r) {
                $atual = $proxPorAluno[$r->id_aluno] ?? null;
                if ($atual === null || $this->rank($r->status) < $this->rank($atual)) {
                    $proxPorAluno[$r->id_aluno] = $r->status;
                }
            }

            // Inadimplência: apenas títulos vencidos em aberto DO PERÍODO ANTERIOR
            // (dívida de outros períodos não conta como inadimplência deste semestre).
            $valorInadPorAluno = DB::table('titulos_abertos')
                ->whereNotNull('id_aluno')
                ->where('id_periodo_letivo', $idAnterior)
                ->groupBy('id_aluno')
                ->selectRaw('id_aluno, sum(valor) as v')
                ->pluck('v', 'id_aluno');

            $NAO = 'NÃO REMATRICULOU';
            $statusSet = [];

            $addPivot = function (array &$pivot, array &$tot, string $chave, array $meta, ?string $statusProx, bool $inad, float $valorInad, bool $formando) use (&$statusSet) {
                if (! isset($pivot[$chave])) {
                    $pivot[$chave] = $meta + ['status' => [], 'total' => 0, 'formandos' => 0, 'base_remat' => 0, 'adimpl' => 0, 'inadimpl' => 0, 'valor_inad' => 0.0];
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

                $addPivot($porCurso, $totCurso,
                    (string) ($m->id_curso_base ?? 'x').'|'.$m->curso,
                    ['id_curso_base' => $m->id_curso_base, 'curso' => $m->curso ?: '(sem curso)'],
                    $statusProx, $inad, $valorInad, $formando);

                $addPivot($porTurma, $totTurma,
                    (string) ($m->id_turma ?? 'x'),
                    ['id_turma' => $m->id_turma, 'turma' => $m->turma ?: '(sem turma)', 'curso' => $m->curso],
                    $statusProx, $inad, $valorInad, $formando);
            }

            // Ordena colunas de status: prioridade conhecida, depois demais, NÃO REMATRICULOU por último.
            $statusCols = $this->ordenarColunas(array_keys($statusSet), $NAO);

            // Ordena linhas por total desc.
            usort($porCurso, fn ($a, $b) => $b['total'] <=> $a['total']);
            usort($porTurma, fn ($a, $b) => $b['total'] <=> $a['total']);

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
            ];
        }

        return view('rematricula.index', compact(
            'periodos', 'idAnterior', 'idProximo', 'pAnterior', 'pProximo',
            'porCurso', 'porTurma', 'statusCols', 'totCurso', 'totTurma', 'resumo'
        ));
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
