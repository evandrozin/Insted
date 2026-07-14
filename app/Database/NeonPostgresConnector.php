<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * Conector Postgres com suporte à Neon em runtimes cujo libpq NÃO tem SNI
 * (caso do runtime PHP da Vercel).
 *
 * Sem SNI, a Neon não consegue descobrir qual endpoint atender e retorna
 * "Endpoint ID is not specified". A correção é enviar o endpoint id
 * explicitamente via parâmetro `options=endpoint=<id>` do libpq.
 *
 * O endpoint id é o primeiro rótulo do host (sem o sufixo "-pooler").
 */
class NeonPostgresConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        $host = $config['host'] ?? '';

        if (str_contains($host, 'neon.tech') && ! str_contains($dsn, 'options=')) {
            $endpoint = str_replace('-pooler', '', explode('.', $host)[0] ?? '');
            if ($endpoint !== '') {
                $dsn .= ";options=endpoint={$endpoint}";
            }
        }

        return $dsn;
    }
}
