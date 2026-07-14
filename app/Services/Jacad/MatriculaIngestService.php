<?php

namespace App\Services\Jacad;

use App\Models\IngestaoLog;
use App\Models\Matricula;
use App\Models\PeriodoLetivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ingestão de períodos letivos e matrículas do JACAD para o Postgres.
 *
 * Estratégia: buscar períodos letivos (anos disponíveis) e, para cada um,
 * paginar as matrículas por cursor (idCursor -> page.idCursorProximo),
 * gravando por upsert idempotente.
 */
class MatriculaIngestService
{
    public function __construct(protected JacadClient $client)
    {
    }

    /** Callback opcional para reportar progresso (ex.: saída do command). */
    protected $onProgress = null;

    public function onProgress(callable $cb): static
    {
        $this->onProgress = $cb;

        return $this;
    }

    protected function log(string $msg): void
    {
        if ($this->onProgress) {
            ($this->onProgress)($msg);
        }
    }

    /**
     * Lista as organizações (idOrganizacao) da instituição.
     * O JACAD exige idOrg em várias consultas acadêmicas.
     *
     * @return array<int, array{id:int, descricao:?string}>
     */
    public function organizacoes(): array
    {
        $resp = $this->client->get('/api/v1/basicos/organizacoes', ['pageSize' => 100]);
        $els = $resp->json('elements', []);

        return array_map(fn ($o) => [
            'id' => (int) $o['idOrganizacao'],
            'descricao' => $o['descricao'] ?? null,
        ], $els);
    }

    /**
     * Sincroniza a lista de períodos letivos de todas as organizações.
     *
     * @return int quantidade de períodos gravados
     */
    public function sincronizarPeriodosLetivos(): int
    {
        $total = 0;

        foreach ($this->organizacoes() as $org) {
            $pageSize = $this->client->pageSize();
            $pagina = 0;

            do {
                $resp = $this->client->get('/api/v1/academico/periodos-letivos/', [
                    'idOrg' => $org['id'],
                    'pageSize' => $pageSize,
                    'currentPage' => $pagina,
                ]);
                $elementos = $resp->json('elements', []);

                foreach ($elementos as $p) {
                    PeriodoLetivo::updateOrCreate(
                        ['id_periodo_letivo' => $p['idPeriodoLetivo']],
                        [
                            'id_org' => $p['idOrg'] ?? $org['id'],
                            'org_descricao' => $p['orgDescricao'] ?? $org['descricao'],
                            'descricao' => $p['descricao'] ?? null,
                            'descricao_especial' => $p['descricaoEspecial'] ?? null,
                            'data_inicio' => $this->data($p['dataInicio'] ?? null),
                            'data_termino' => $this->data($p['dataTermino'] ?? null),
                            'situacao' => $p['situacao'] ?? null,
                            'ano' => $p['ano'] ?? null,
                            'semestre' => $p['semestre'] ?? null,
                            'periodo_atual' => $p['periodoAtual'] ?? null,
                            'tipo' => $p['tipo'] ?? null,
                            'consolidado' => $p['consolidado'] ?? null,
                            'raw' => $p,
                            'sincronizado_em' => now(),
                        ]
                    );
                    $total++;
                }

                $pagina++;
            } while (count($elementos) === $pageSize);

            $this->log("Org {$org['id']} ({$org['descricao']}): períodos processados.");
        }

        $this->log("Períodos letivos sincronizados: {$total}");

        return $total;
    }

    /**
     * Sincroniza os cursos base.
     *
     * @return int quantidade de cursos gravados
     */
    public function sincronizarCursosBase(): int
    {
        $pageSize = $this->client->pageSize();
        $pagina = 0;
        $total = 0;
        $totalPaginas = null;

        do {
            $resp = $this->client->get('/api/v1/academico/cursos-base/', [
                'pageSize' => $pageSize,
                'currentPage' => $pagina,
            ]);
            $elementos = $resp->json('elements', []);
            $totalPaginas ??= (int) $resp->json('page.totalPages', 0);

            foreach ($elementos as $c) {
                \App\Models\CursoBase::updateOrCreate(
                    ['id_curso_base' => $c['idCursoBase']],
                    [
                        'nome_impressao' => $c['nomeImpressao'] ?? null,
                        'nome_reduzido' => $c['nomeReduzido'] ?? null,
                        'codigo_curso' => $c['codigoCurso'] ?? null,
                        'modalidade' => $c['modalidade'] ?? null,
                        'status' => $c['status'] ?? null,
                        'grau_academico' => $c['grauAcademicoMasculino'] ?? null,
                        'org' => $c['org'] ?? null,
                        'raw' => $c,
                        'sincronizado_em' => now(),
                    ]
                );
                $total++;
            }

            $pagina++;
        } while ($totalPaginas > 0 && $pagina < $totalPaginas);

        $this->log("Cursos base sincronizados: {$total}");

        return $total;
    }

    /**
     * Sincroniza as matrizes curriculares (com a duração em semestres).
     *
     * @return int quantidade de matrizes gravadas
     */
    public function sincronizarMatrizes(): int
    {
        $pageSize = $this->client->pageSize();
        $pagina = 0;
        $total = 0;
        $totalPaginas = null;

        do {
            $resp = $this->client->get('/api/v1/academico/matrizes/', [
                'pageSize' => $pageSize,
                'currentPage' => $pagina,
            ]);
            $elementos = $resp->json('elements', []);
            $totalPaginas ??= (int) $resp->json('page.totalPages', 0);

            foreach ($elementos as $m) {
                \App\Models\Matriz::updateOrCreate(
                    ['id_curso_matriz' => $m['idCurso']],
                    [
                        'id_curso_base' => $m['idCursoBase'] ?? null,
                        'nome' => $m['nome'] ?? null,
                        'prazo_conclusao' => $m['prazoConclusao'] ?? null,
                        'prazo_em' => $m['prazoEm'] ?? null,
                        'periodicidade' => $m['periodicidade'] ?? null,
                        'total_semestres' => $this->totalSemestres($m),
                        'status' => $m['status'] ?? null,
                        'raw' => $m,
                        'sincronizado_em' => now(),
                    ]
                );
                $total++;
            }

            $pagina++;
        } while ($totalPaginas > 0 && $pagina < $totalPaginas);

        $this->log("Matrizes sincronizadas: {$total}");

        return $total;
    }

    /** Calcula o total de semestres da matriz a partir de prazo/periodicidade. */
    protected function totalSemestres(array $m): ?int
    {
        $n = (int) ($m['prazoConclusao'] ?? 0);
        if ($n <= 0) {
            return null;
        }
        $em = strtoupper($m['prazoEm'] ?? '');
        $per = strtoupper($m['periodicidade'] ?? '');

        if (str_contains($em, 'ANO')) {
            return str_contains($per, 'SEMESTR') ? $n * 2 : $n; // anos: semestral=×2, anual=×1
        }

        return $n; // já em semestres/períodos
    }

    /** Extrai o número do período/semestre de textos como "9º Semestre". */
    protected function periodoNumero(?string $texto): ?int
    {
        if (! $texto || ! preg_match('/(\d+)/', $texto, $mm)) {
            return null;
        }

        return (int) $mm[1];
    }

    /**
     * Sincroniza turmas de todos os períodos letivos e status.
     * O endpoint exige turmaIdPeriodoLetivo e turmaStatus.
     *
     * @return int quantidade de turmas gravadas
     */
    public function sincronizarTurmas(?int $ano = null): int
    {
        $statuses = ['ATIVA', 'AGUARDANDO', 'ENCERRADA', 'CANCELADA'];
        $pageSize = $this->client->pageSize();

        $query = PeriodoLetivo::query()->orderBy('ano')->orderBy('semestre');
        if ($ano) {
            $query->where('ano', $ano);
        }
        $periodos = $query->get();

        $total = 0;
        foreach ($periodos as $periodo) {
            foreach ($statuses as $status) {
                $pagina = 0;
                $totalPaginas = null;

                do {
                    $resp = $this->client->get('/api/v1/academico/turmas', [
                        'turmaIdPeriodoLetivo' => $periodo->id_periodo_letivo,
                        'turmaStatus' => $status,
                        'pageSize' => $pageSize,
                        'currentPage' => $pagina,
                    ]);
                    $elementos = $resp->json('elements', []);
                    $totalPaginas ??= (int) $resp->json('page.totalPages', 0);

                    foreach ($elementos as $t) {
                        \App\Models\Turma::updateOrCreate(
                            ['id_turma' => $t['idTurma']],
                            [
                                'nome' => $t['turmaNome'] ?? null,
                                'nome_reduzido' => $t['turmaNomeRed'] ?? null,
                                'id_curso' => $t['turmaIdCurso'] ?? null,
                                'curso' => $t['turmaCurso'] ?? null,
                                'id_matriz' => $t['turmaIdMatriz'] ?? null,
                                'matriz' => $t['turmaMatriz'] ?? null,
                                'id_periodo_letivo' => $t['turmaIdPeriodoLetivo'] ?? $periodo->id_periodo_letivo,
                                'periodo_letivo' => $t['turmaPeriodoLetivo'] ?? null,
                                'id_unidade_fisica' => $t['turmaIdUnidadeFisica'] ?? null,
                                'unidade_fisica' => $t['turmaUnidadeFisica'] ?? null,
                                'turno' => $t['turmaTurno'] ?? null,
                                'periodo_item' => $t['turmaPeriodoItem'] ?? null,
                                'periodo_numero' => $this->periodoNumero($t['turmaPeriodoItem'] ?? null),
                                'status' => $t['turmaStatus'] ?? $status,
                                'id_org' => $t['idOrg'] ?? null,
                                'org_descricao' => $t['orgDescricao'] ?? null,
                                'data_inicio' => $this->data($t['turmaDataInicio'] ?? null),
                                'data_fim' => $this->data($t['turmaDataFim'] ?? null),
                                'qtde_disciplina' => $t['turmaQtdeDisciplina'] ?? null,
                                'raw' => $t,
                                'sincronizado_em' => now(),
                            ]
                        );
                        $total++;
                    }

                    $pagina++;
                    if ($totalPaginas === 0) {
                        break;
                    }
                } while ($pagina < $totalPaginas);
            }
            $this->log("Turmas do período {$periodo->descricao} processadas (total {$total}).");
        }

        $this->log("Turmas sincronizadas: {$total}");

        return $total;
    }

    /**
     * Sincroniza os títulos (receitas) em aberto e VENCIDOS, para determinar
     * inadimplência. Fonte: GET /api/v1/financeiro/consolidacao/receitas
     * (read-only). Considera vencimentos de 2010-01-01 até hoje.
     *
     * @return int quantidade de títulos gravados
     */
    public function sincronizarTitulosAbertos(): int
    {
        // Limpa a base (títulos que foram pagos deixam de vir como ABERTO).
        \App\Models\TituloAberto::query()->delete();

        $pageSize = $this->client->pageSize();
        $pagina = 0;
        $total = 0;
        $totalPaginas = null;
        $hoje = now()->toDateString();

        do {
            $resp = $this->client->get('/api/v1/financeiro/consolidacao/receitas', [
                'situacao' => 'ABERTO',
                'dataVencimentoInicio' => '2010-01-01',
                'dataVencimentoTermino' => $hoje,
                'pageSize' => $pageSize,
                'currentPage' => $pagina,
            ]);

            $elementos = $resp->json('elements', []);
            $totalPaginas ??= (int) $resp->json('page.totalPages', 0);

            if (empty($elementos)) {
                break;
            }

            $linhas = array_map(function ($t) use ($hoje) {
                return [
                    'id_transacao' => $t['idTransacao'],
                    'id_aluno' => $t['alunoIdAluno'] ?? null,
                    'id_matricula' => $t['matriculaIdMatricula'] ?? null,
                    'id_periodo_letivo' => $t['periodoLetivoIdPeriodoLetivo'] ?? null,
                    'id_curso' => $t['cursoIdCurso'] ?? null,
                    'curso' => $t['curso'] ?? null,
                    'id_turma' => $t['turmaIdTurma'] ?? null,
                    'turma' => $t['turmaNomeReduzido'] ?? ($t['turma'] ?? null),
                    'pagador_nome' => $t['pagadorNome'] ?? null,
                    'pagador_cpf' => $t['pagadorCpfCnpj'] ?? null,
                    'situacao' => $t['transacaoSituacao'] ?? null,
                    'origem' => $t['transacaoOrigem'] ?? null,
                    'data_vencimento' => $this->data($t['transacaoDataVencimento'] ?? null),
                    'valor' => $t['transacaoValor'] ?? null,
                    'matricula_status' => $t['matriculaStatus'] ?? null,
                    'id_org' => $t['idOrg'] ?? null,
                    'sincronizado_em' => $hoje,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $elementos);

            \Illuminate\Support\Facades\DB::table('titulos_abertos')->upsert(
                $linhas, ['id_transacao'],
                ['id_aluno', 'id_matricula', 'data_vencimento', 'valor', 'situacao', 'curso', 'turma', 'sincronizado_em', 'updated_at']
            );

            $total += count($elementos);
            $pagina++;
            $this->log("  títulos em aberto: página {$pagina}/".max($totalPaginas, 1)." (total {$total})");

            usleep(((int) config('jacad.sleep_ms_entre_paginas', 150)) * 1000);
        } while ($totalPaginas > 0 && $pagina < $totalPaginas);

        $this->log("Títulos em aberto (vencidos) sincronizados: {$total}");

        return $total;
    }

    /**
     * Sincroniza matrículas de um período letivo, paginando por currentPage.
     *
     * Usa o endpoint v1: o v2 apresenta um bug de SQL no backend do JACAD para
     * este usuário de integração (subquery de permissões de organização).
     *
     * @return array{registros:int, paginas:int}
     */
    public function sincronizarMatriculasDoPeriodo(int $idPeriodoLetivo, ?int $idOrg = null): array
    {
        $pageSize = $this->client->pageSize();
        $pagina = 0;
        $registros = 0;
        $totalPaginas = null;

        do {
            $resp = $this->client->get('/api/v1/academico/matriculas', [
                'idPeriodoLetivo' => $idPeriodoLetivo,
                'pageSize' => $pageSize,
                'currentPage' => $pagina,
            ]);

            $elementos = $resp->json('elements', []);
            $totalPaginas ??= (int) $resp->json('page.totalPages', 0);

            if (empty($elementos)) {
                break;
            }

            $this->gravarLote($elementos);
            $registros += count($elementos);
            $this->log("  período {$idPeriodoLetivo}: página ".($pagina + 1).'/'.max($totalPaginas, 1)." (+".count($elementos)." | total {$registros})");

            $pagina++;
            if ($totalPaginas > 0 && $pagina >= $totalPaginas) {
                break;
            }

            usleep(((int) config('jacad.sleep_ms_entre_paginas', 150)) * 1000);
        } while (true);

        return ['registros' => $registros, 'paginas' => $pagina];
    }

    /**
     * Sincroniza matrículas de TODOS os períodos letivos conhecidos.
     * Se ainda não houver períodos, busca-os primeiro.
     */
    public function sincronizarTudo(?int $ano = null): IngestaoLog
    {
        $reg = IngestaoLog::create([
            'tipo' => 'matriculas',
            'referencia' => $ano ? "ano={$ano}" : 'todos',
            'status' => 'executando',
            'iniciado_em' => now(),
        ]);

        try {
            if (PeriodoLetivo::count() === 0) {
                $this->sincronizarPeriodosLetivos();
            }

            $query = PeriodoLetivo::query()->orderBy('ano')->orderBy('semestre');
            if ($ano) {
                $query->where('ano', $ano);
            }
            $periodos = $query->get();

            $totalReg = 0;
            $totalPag = 0;
            foreach ($periodos as $periodo) {
                $this->log("Período {$periodo->descricao} (id {$periodo->id_periodo_letivo}, ano {$periodo->ano})");
                $r = $this->sincronizarMatriculasDoPeriodo($periodo->id_periodo_letivo, $periodo->id_org);
                $totalReg += $r['registros'];
                $totalPag += $r['paginas'];
            }

            $reg->marcarConcluido($totalReg, $totalPag, "Períodos: {$periodos->count()}");

            return $reg;
        } catch (\Throwable $e) {
            $reg->marcarErro($e->getMessage());
            throw $e;
        }
    }

    /** Upsert de um lote de matrículas em uma única query. */
    protected function gravarLote(array $elementos): void
    {
        $agora = now();
        $linhas = array_map(function (array $m) use ($agora) {
            return [
                'id_matricula' => $m['idMatricula'],
                'id_turma' => $m['idTurma'] ?? null,
                'id_periodo_letivo' => $m['idPeriodoLetivo'] ?? null,
                'id_aluno_curso_ingresso' => $m['idAlunoCursoIngresso'] ?? null,
                'id_aluno' => $m['idAluno'] ?? null,
                'id_perfil_aluno' => $m['idPerfilAluno'] ?? null,
                'aluno' => $m['aluno'] ?? null,
                'ra' => $m['ra'] ?? null,
                'ra_estadual' => $m['raEstadual'] ?? null,
                'periodo_letivo' => $m['periodoLetivo'] ?? null,
                'id_curso_base' => $m['idCursoBase'] ?? null,
                'curso' => $m['curso'] ?? null,
                'turma' => $m['turma'] ?? null,
                'status' => $m['status'] ?? null,
                'id_curso_matriz' => $m['idCursoMatriz'] ?? null,
                'matriz' => $m['matriz'] ?? null,
                'aluno_email' => $m['alunoEmail'] ?? null,
                'aluno_email_institucional' => $m['alunoEmailInstitucional'] ?? null,
                'data_matricula' => $this->data($m['dataMatricula'] ?? null),
                'data_ativacao' => $this->data($m['dataAtivacao'] ?? null),
                'data_trancamento' => $this->data($m['dataTrancamento'] ?? null),
                'data_cadastro' => $this->data($m['dataCadastro'] ?? null),
                'id_unidade_fisica' => $m['idUnidadeFisica'] ?? null,
                'unidade_fisica' => $m['unidadeFisica'] ?? null,
                'id_org' => $m['idOrg'] ?? null,
                'organizacao' => $m['organizacao'] ?? null,
                'data_criacao_api' => $this->dataHora($m['dataCriacao'] ?? null),
                'data_alteracao_api' => $this->dataHora($m['dataAlteracao'] ?? null),
                'raw' => json_encode($m, JSON_UNESCAPED_UNICODE),
                'sincronizado_em' => $agora,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }, $elementos);

        // Colunas atualizadas em conflito (todas menos a PK e created_at).
        $update = array_keys($linhas[0]);
        $update = array_values(array_diff($update, ['id_matricula', 'created_at']));

        DB::table('matriculas')->upsert($linhas, ['id_matricula'], $update);
    }

    protected function data(?string $v): ?string
    {
        return $v ? Carbon::parse($v)->toDateString() : null;
    }

    protected function dataHora(?string $v): ?string
    {
        return $v ? Carbon::parse($v)->toDateTimeString() : null;
    }
}
