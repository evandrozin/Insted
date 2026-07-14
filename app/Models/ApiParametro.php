<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiParametro extends Model
{
    protected $table = 'api_parametros';

    protected $fillable = [
        'nome', 'descricao', 'base_url', 'usuario',
        'token', 'page_size', 'ativo', 'extra',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'page_size' => 'integer',
        'extra' => 'array',
    ];

    /** Busca a configuração de uma integração pelo nome lógico. */
    public static function porNome(string $nome): ?self
    {
        return static::where('nome', $nome)->where('ativo', true)->first();
    }
}
