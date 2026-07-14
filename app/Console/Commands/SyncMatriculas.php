<?php

namespace App\Console\Commands;

use App\Services\Jacad\JacadClient;
use App\Services\Jacad\MatriculaIngestService;
use Illuminate\Console\Command;

class SyncMatriculas extends Command
{
    protected $signature = 'jacad:sync-matriculas
                            {--ano= : Sincroniza apenas o ano informado (ex.: 2025)}
                            {--somente-periodos : Apenas atualiza a lista de períodos letivos}
                            {--somente-cursos : Apenas atualiza os cursos base}
                            {--somente-turmas : Apenas atualiza as turmas}
                            {--somente-titulos : Apenas atualiza os títulos em aberto (inadimplência)}';

    protected $description = 'Sincroniza períodos letivos, cursos, turmas e matrículas do JACAD para o Postgres';

    public function handle(): int
    {
        $service = (new MatriculaIngestService(new JacadClient()))
            ->onProgress(fn (string $m) => $this->line($m));

        try {
            $this->info('Autenticando no JACAD...');
            $ano = $this->option('ano') ? (int) $this->option('ano') : null;

            if ($this->option('somente-periodos')) {
                $this->info('Concluído: '.$service->sincronizarPeriodosLetivos().' períodos letivos.');

                return self::SUCCESS;
            }

            if ($this->option('somente-cursos')) {
                $this->info('Concluído: '.$service->sincronizarCursosBase().' cursos base.');

                return self::SUCCESS;
            }

            if ($this->option('somente-turmas')) {
                $this->info('Concluído: '.$service->sincronizarTurmas($ano).' turmas.');

                return self::SUCCESS;
            }

            if ($this->option('somente-titulos')) {
                $this->info('Concluído: '.$service->sincronizarTitulosAbertos().' títulos em aberto.');

                return self::SUCCESS;
            }

            // Full: garante períodos, cursos, matrizes, turmas e títulos antes das matrículas.
            $service->sincronizarCursosBase();
            $service->sincronizarMatrizes();
            $service->sincronizarTurmas($ano);
            $service->sincronizarTitulosAbertos();

            $this->info($ano ? "Sincronizando matrículas do ano {$ano}..." : 'Sincronizando matrículas de todos os anos...');
            $reg = $service->sincronizarTudo($ano);

            $this->newLine();
            $this->info("Concluído. Matrículas: {$reg->total_registros} | Páginas: {$reg->total_paginas} | Log #{$reg->id}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falha na sincronização: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
