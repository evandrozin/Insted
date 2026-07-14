<?php

/*
 * Endpoint TEMPORÁRIO de diagnóstico do deploy na Vercel.
 * Testa versão do PHP, extensões, variáveis de ambiente e a conexão
 * com o banco — SEM depender do Laravel. Remova depois de resolver.
 */
header('Content-Type: text/plain; charset=utf-8');

$env = function (string $k) {
    $v = getenv($k);
    if ($v === false && isset($_ENV[$k])) {
        $v = $_ENV[$k];
    }
    if ($v === false && isset($_SERVER[$k])) {
        $v = $_SERVER[$k];
    }

    return $v === false ? null : $v;
};

echo "PHP version   : ".PHP_VERSION."\n";
echo "pdo_pgsql     : ".(extension_loaded('pdo_pgsql') ? 'sim' : 'NÃO')."\n";
echo "openssl       : ".(extension_loaded('openssl') ? 'sim' : 'NÃO')."\n";
echo "\n--- Variáveis de ambiente ---\n";
foreach (['APP_ENV', 'APP_DEBUG', 'APP_KEY', 'DB_CONNECTION', 'DB_URL', 'DB_HOST', 'LOG_CHANNEL', 'SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION'] as $k) {
    $v = $env($k);
    if (in_array($k, ['APP_KEY', 'DB_URL'], true)) {
        $v = $v ? '(definida, '.strlen($v).' caracteres)' : '(AUSENTE)';
    }
    echo str_pad($k, 18).': '.($v ?? '(AUSENTE)')."\n";
}

echo "\n--- Teste de conexão com o banco ---\n";
$url = $env('DB_URL');
if (! $url) {
    echo "DB_URL ausente — o Laravel usaria o driver padrão (sqlite) e quebraria.\n";
    exit;
}

try {
    $p = parse_url($url);
    $host = $p['host'] ?? '';
    $port = $p['port'] ?? 5432;
    $db = ltrim($p['path'] ?? '', '/');
    $user = urldecode($p['user'] ?? '');
    $pass = urldecode($p['pass'] ?? '');
    echo "host          : $host\n";
    echo "database      : $db\n";
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 8, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $users = $pdo->query('select count(*) from users')->fetchColumn();
    $sessions = $pdo->query("select count(*) from information_schema.tables where table_name='sessions'")->fetchColumn();
    echo "conexão       : OK\n";
    echo "users         : $users\n";
    echo "tabela sessions: ".($sessions ? 'existe' : 'NÃO EXISTE')."\n";
} catch (Throwable $e) {
    echo "conexão       : ERRO\n";
    echo 'mensagem      : '.$e->getMessage()."\n";
}
