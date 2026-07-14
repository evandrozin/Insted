<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integração JACAD / SWA (Insted)
    |--------------------------------------------------------------------------
    |
    | Valores padrão da integração. Estes valores servem de fallback: a fonte
    | de verdade em runtime é a tabela `api_parametros` (módulo de Parâmetros
    | de API), que pode ser editada pelo painel sem alterar código.
    |
    */

    'base_url' => rtrim(env('JACAD_BASE_URL', 'https://insted-developer.jacad.com.br'), '/'),

    'usuario' => env('JACAD_USUARIO', 'integracao'),

    'token' => env('JACAD_TOKEN', ''),

    // Registros por página nas consultas paginadas.
    'page_size' => (int) env('JACAD_PAGE_SIZE', 200),

    // Tempo (segundos) para expirar o cache do access token antes do expiresIn.
    'token_skew' => 60,

    // Rate limit técnico do JACAD: 10 req/s por IP. Mantemos folga.
    'sleep_ms_entre_paginas' => (int) env('JACAD_SLEEP_MS', 150),

    // Timeout das requisições HTTP (segundos).
    'timeout' => (int) env('JACAD_TIMEOUT', 60),

    /*
    | Grupos de API disponíveis na instância (para referência/expansão futura).
    */
    'grupos' => [
        'auth', 'academico', 'basico', 'controle-acesso',
        'financeiro', 'integracoes', 'processo-seletivo',
        'queries', 'requerimentos',
    ],
];
