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
$secretas = ['APP_KEY', 'DB_URL', 'DATABASE_URL', 'DATABASE_URL_UNPOOLED', 'POSTGRES_URL_NON_POOLING'];
foreach (['APP_ENV', 'APP_DEBUG', 'APP_KEY', 'DB_CONNECTION', 'DB_URL', 'DATABASE_URL', 'DATABASE_URL_UNPOOLED', 'POSTGRES_URL_NON_POOLING', 'LOG_CHANNEL'] as $k) {
    $v = $env($k);
    if (in_array($k, $secretas, true)) {
        $v = $v ? '(definida, '.strlen($v).' caracteres)' : '(AUSENTE)';
    }
    echo str_pad($k, 26).': '.($v ?? '(AUSENTE)')."\n";
}

echo "\n--- Teste de conexão com o banco ---\n";
// Mesmo fallback do config/database.php.
$url = $env('DB_URL') ?: ($env('DATABASE_URL_UNPOOLED') ?: ($env('POSTGRES_URL_NON_POOLING') ?: $env('DATABASE_URL')));
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
    // Neon sem SNI: injeta o endpoint id (primeiro rótulo do host, sem -pooler).
    if (str_contains($host, 'neon.tech')) {
        $endpoint = str_replace('-pooler', '', explode('.', $host)[0]);
        $dsn .= ";options=endpoint=$endpoint";
        echo "endpoint      : $endpoint\n";
    }
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

echo "\n--- Requisição HTTP real /login (debug forçado) ---\n";
// Força o debug SÓ neste teste para capturar a exceção verdadeira.
putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';

try {
    $storage = '/tmp/storage';
    foreach (['/framework/views', '/framework/cache/data', '/framework/sessions', '/logs'] as $d) {
        if (! is_dir($storage.$d)) {
            @mkdir($storage.$d, 0755, true);
        }
    }
    foreach (['APP_PACKAGES_CACHE' => $storage.'/framework/packages.php', 'APP_SERVICES_CACHE' => $storage.'/framework/services.php', 'APP_EVENTS_CACHE' => $storage.'/framework/events.php'] as $k => $v) {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }

    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->useStoragePath($storage);

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $kernel->handle($request);

    echo 'status /login      : '.$response->getStatusCode()."\n";
    if ($response->getStatusCode() >= 400) {
        $txt = trim(preg_replace('/\s+/', ' ', strip_tags($response->getContent())));
        echo "corpo (início)     :\n".substr($txt, 0, 1200)."\n";
    } else {
        echo "OK — login renderizou.\n";
    }
} catch (Throwable $e) {
    echo 'EXCEÇÃO            : '.get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString()."\n";
}
