<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
 * Entrypoint da Vercel (runtime PHP da comunidade).
 *
 * Na Vercel o sistema de arquivos é somente-leitura, exceto /tmp. Por isso
 * apontamos o storage do Laravel para /tmp e garantimos a árvore de pastas
 * que o framework precisa escrever (views compiladas, cache, sessões, logs).
 * Cache, sessão e fila deste projeto usam o banco, então não dependem de disco.
 */
$storage = '/tmp/storage';
$dirs = [
    '/framework', '/framework/cache', '/framework/cache/data',
    '/framework/views', '/framework/sessions', '/framework/testing',
    '/app', '/app/public', '/logs',
];
foreach ($dirs as $dir) {
    if (! is_dir($storage.$dir)) {
        @mkdir($storage.$dir, 0755, true);
    }
}

// Autoloader do Composer...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap do Laravel...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Redireciona o storage para /tmp (gravável). Precisa vir antes de tratar a
// requisição, para que storage_path() e as views compiladas usem /tmp.
$app->useStoragePath($storage);

$app->handleRequest(Request::capture());
