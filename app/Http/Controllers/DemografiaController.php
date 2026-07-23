<?php

namespace App\Http\Controllers;

use App\Models\CursoBase;
use App\Models\PeriodoLetivo;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Distribuição geográfica dos alunos (cidade e bairro), em gráficos de pizza.
 *
 * A origem do endereço é o perfil da pessoa (tabela `perfis`), ligado à
 * matrícula por `id_perfil_aluno`. A contagem é por ALUNO distinto — um aluno
 * com mais de uma matrícula no período conta uma única vez.
 */
class DemografiaController extends Controller
{
    /** Quantas fatias nomeadas exibir antes de agrupar o resto em "Outros". */
    protected const TOP_FATIAS = 12;

    /** Paleta das fatias (repete se necessário). */
    protected const CORES = [
        '#17BEB8', '#2C6BED', '#F5A623', '#E5484D', '#17A34A', '#8B5CF6',
        '#EC4899', '#0EA5E9', '#84CC16', '#F97316', '#A16207', '#0F766E',
    ];

    protected const NAO_INFORMADO = '(não informado)';

    /**
     * Textos que os operadores digitam no lugar de deixar o bairro em branco.
     * Sem isso o gráfico mostraria "NÃO INFORMADO" como se fosse um bairro,
     * separado das linhas realmente vazias.
     */
    protected const BAIRROS_VAZIOS = [
        'NAO INFORMADO', 'NÃO INFORMADO', 'NAO INFORMADA', 'NÃO INFORMADA',
        'NAO INFORMOU', 'NÃO INFORMOU', 'SEM BAIRRO', 'NENHUM', 'N/I', 'NI',
        'N/A', 'NA', '-', '--', '.', '..', 'X', 'XX', '0', 'A CONFIRMAR',
    ];

    public function index(Request $request)
    {
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')
            ->get(['id_periodo_letivo', 'descricao', 'org_descricao', 'ano']);

        return view('demografia.index', $this->computar($request) + [
            'periodos' => $periodos,
            'cursos' => $this->cursosDoContexto($request),
            'temPerfis' => DB::table('perfis')->exists(),
        ]);
    }

    /**
     * Versão para impressão/PDF do mesmo relatório, com os filtros da tela.
     * Ao contrário dos gráficos, as tabelas saem completas — é um relatório.
     */
    public function exportarPdf(Request $request)
    {
        return view('demografia.export_pdf', $this->computar($request) + [
            'filtros' => $this->descreverFiltros($request),
        ]);
    }

    /**
     * Apura os números do relatório. Tela e PDF passam por aqui, para não
     * existir a chance de divergirem.
     *
     * @return array<string, mixed>
     */
    protected function computar(Request $request): array
    {
        $totalAlunos = (int) $this->base($request)->distinct()->count('matriculas.id_aluno');

        $porCidade = $this->agrupar($request, "
            case
                when cidades.descricao is null or trim(cidades.descricao) = '' then '".self::NAO_INFORMADO."'
                else trim(cidades.descricao) || coalesce(' / ' || nullif(trim(cidades.uf), ''), '')
            end
        ");

        $vazios = implode(', ', array_map(fn ($b) => "'".$b."'", self::BAIRROS_VAZIOS));
        $porBairro = $this->agrupar($request, "
            case
                when coalesce(upper(trim(perfis.bairro)), '') in ('', {$vazios}) then '".self::NAO_INFORMADO."'
                else upper(trim(perfis.bairro))
            end
        ");

        return [
            'totalAlunos' => $totalAlunos,
            'cidades' => $this->montarFatias($porCidade, $totalAlunos),
            'bairros' => $this->montarFatias($porBairro, $totalAlunos),
            'linhasCidade' => $this->montarLinhas($porCidade, $totalAlunos),
            'linhasBairro' => $this->montarLinhas($porBairro, $totalAlunos),
            // Cobertura: quantos alunos têm endereço preenchido no perfil.
            'semCidade' => $this->totalDoRotulo($porCidade, self::NAO_INFORMADO),
            'semEndereco' => $this->totalDoRotulo($porBairro, self::NAO_INFORMADO),
        ];
    }

    /**
     * Filtros aplicados, em texto, para o cabeçalho do relatório.
     *
     * @return array<string, string>
     */
    protected function descreverFiltros(Request $request): array
    {
        $filtros = [];

        if ($request->filled('periodo')) {
            $p = PeriodoLetivo::find($request->integer('periodo'));
            $filtros['Período letivo'] = $p
                ? trim($p->descricao.' · '.Str::of($p->org_descricao)->title())
                : '#'.$request->integer('periodo');
        }

        if ($request->filled('curso')) {
            $c = CursoBase::find($request->integer('curso'));
            $filtros['Curso'] = $c
                ? $c->nome_impressao.($c->modalidade ? ' - '.$c->modalidade : '')
                : '#'.$request->integer('curso');
        }

        return $filtros ?: ['Abrangência' => 'Todos os períodos e cursos'];
    }

    /**
     * Cursos que têm aluno no período selecionado — só eles entram no filtro.
     *
     * Repara que aqui NÃO se aplica o filtro de curso: senão, ao escolher um
     * curso a própria lista encolheria para ele e não daria para trocar.
     *
     * O join com `cursos_base` é à esquerda de propósito: há matrícula cujo
     * curso não está no cadastro, e um join interno a esconderia do filtro.
     *
     * @return Collection<int, object>
     */
    protected function cursosDoContexto(Request $request)
    {
        $cursos = DB::table('matriculas')
            ->leftJoin('cursos_base', 'matriculas.id_curso_base', '=', 'cursos_base.id_curso_base')
            ->whereNotNull('matriculas.id_aluno')
            ->whereNotNull('matriculas.id_curso_base')
            ->when($request->filled('periodo'),
                fn ($q) => $q->where('matriculas.id_periodo_letivo', $request->integer('periodo')))
            ->selectRaw('matriculas.id_curso_base,
                coalesce(max(cursos_base.nome_impressao), max(matriculas.curso)) as nome_impressao,
                max(cursos_base.modalidade) as modalidade')
            ->groupBy('matriculas.id_curso_base')
            ->orderBy('nome_impressao')
            ->get();

        // O curso já filtrado pode não existir no período recém-escolhido.
        // Sem isto o select voltaria em "Todos" enquanto o filtro seguiria valendo.
        if ($request->filled('curso') && ! $cursos->contains('id_curso_base', $request->integer('curso'))) {
            $selecionado = CursoBase::find($request->integer('curso'));
            $cursos->push((object) [
                'id_curso_base' => $request->integer('curso'),
                'nome_impressao' => ($selecionado->nome_impressao ?? 'Curso #'.$request->integer('curso')).' (sem aluno no período)',
                'modalidade' => null,
            ]);
        }

        return $cursos;
    }

    /** Query base: matrículas do período escolhido, com o endereço do perfil. */
    protected function base(Request $request): Builder
    {
        $base = DB::table('matriculas')
            ->leftJoin('perfis', 'matriculas.id_perfil_aluno', '=', 'perfis.id_perfil')
            ->leftJoin('cidades', 'perfis.id_cidade', '=', 'cidades.id_cidade')
            ->whereNotNull('matriculas.id_aluno');

        if ($request->filled('periodo')) {
            $base->where('matriculas.id_periodo_letivo', $request->integer('periodo'));
        }
        if ($request->filled('curso')) {
            $base->where('matriculas.id_curso_base', $request->integer('curso'));
        }

        return $base;
    }

    /**
     * Conta alunos distintos por um rótulo (expressão SQL), do maior para o menor.
     *
     * @return list<array{rotulo:string, total:int}>
     */
    protected function agrupar(Request $request, string $expressaoRotulo): array
    {
        $expressao = preg_replace('/\s+/', ' ', trim($expressaoRotulo));

        return $this->base($request)
            ->selectRaw("{$expressao} as rotulo, count(distinct matriculas.id_aluno) as total")
            ->groupByRaw($expressao)
            ->orderByDesc('total')
            ->orderBy('rotulo')
            ->get()
            ->map(fn ($r) => ['rotulo' => (string) $r->rotulo, 'total' => (int) $r->total])
            ->all();
    }

    /** Total de um rótulo específico dentro de um agrupamento. */
    protected function totalDoRotulo(array $grupos, string $rotulo): int
    {
        foreach ($grupos as $g) {
            if ($g['rotulo'] === $rotulo) {
                return $g['total'];
            }
        }

        return 0;
    }

    /**
     * Fatias do gráfico: as TOP_FATIAS maiores + "Outros" com o restante.
     *
     * @return list<array{rotulo:string, total:int, pct:float, cor:string}>
     */
    protected function montarFatias(array $grupos, int $total): array
    {
        if ($total <= 0) {
            return [];
        }

        $principais = array_slice($grupos, 0, self::TOP_FATIAS);
        $resto = array_slice($grupos, self::TOP_FATIAS);

        if ($resto) {
            $principais[] = [
                'rotulo' => 'Outros ('.count($resto).')',
                'total' => array_sum(array_column($resto, 'total')),
            ];
        }

        $fatias = [];
        foreach ($principais as $i => $g) {
            $fatias[] = [
                'rotulo' => $g['rotulo'],
                'total' => $g['total'],
                'pct' => round($g['total'] / $total * 100, 1),
                'cor' => $g['rotulo'] === self::NAO_INFORMADO
                    ? '#c3c7cd'
                    : self::CORES[$i % count(self::CORES)],
            ];
        }

        return $fatias;
    }

    /**
     * Linhas da tabela detalhada (todos os rótulos, com percentual).
     *
     * @return list<array{rotulo:string, total:int, pct:float}>
     */
    protected function montarLinhas(array $grupos, int $total): array
    {
        return array_map(fn ($g) => [
            'rotulo' => $g['rotulo'],
            'total' => $g['total'],
            'pct' => $total > 0 ? round($g['total'] / $total * 100, 1) : 0.0,
        ], $grupos);
    }
}
