<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Geração de exportações sem dependências externas:
 *  - Excel: CSV UTF-8 (BOM + separador ";") que o Excel pt-BR abre em colunas.
 */
final class Exportador
{
    /**
     * Monta um CSV para download.
     *
     * @param  string  $nome  nome do arquivo (sem extensão)
     * @param  list<string>  $colunas  cabeçalho
     * @param  iterable<array<int, scalar|null>>  $linhas
     */
    public static function csv(string $nome, array $colunas, iterable $linhas): StreamedResponse
    {
        $arquivo = $nome.'_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($colunas, $linhas) {
            $out = fopen('php://output', 'w');
            // BOM para o Excel reconhecer UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $colunas, ';');
            foreach ($linhas as $linha) {
                fputcsv($out, $linha, ';');
            }
            fclose($out);
        }, $arquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
