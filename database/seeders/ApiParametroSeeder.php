<?php

namespace Database\Seeders;

use App\Models\ApiParametro;
use Illuminate\Database\Seeder;

class ApiParametroSeeder extends Seeder
{
    public function run(): void
    {
        ApiParametro::updateOrCreate(
            ['nome' => 'jacad'],
            [
                'descricao' => 'API de Integração JACAD / SWA - Insted',
                'base_url' => config('jacad.base_url'),
                'usuario' => config('jacad.usuario'),
                'token' => config('jacad.token'),
                'page_size' => config('jacad.page_size', 200),
                'ativo' => true,
                'extra' => [
                    'grupos' => config('jacad.grupos'),
                    'auth_endpoint' => '/api/v1/auth/token',
                    'auth_header' => 'token',
                    'access_token_header' => 'Authorization',
                ],
            ]
        );
    }
}
