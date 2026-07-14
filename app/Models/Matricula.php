<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $table = 'matriculas';
    protected $primaryKey = 'id_matricula';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'data_matricula' => 'date',
        'data_ativacao' => 'date',
        'data_trancamento' => 'date',
        'data_cadastro' => 'date',
        'data_criacao_api' => 'datetime',
        'data_alteracao_api' => 'datetime',
        'sincronizado_em' => 'datetime',
        'raw' => 'array',
    ];

    public function periodoLetivo()
    {
        return $this->belongsTo(PeriodoLetivo::class, 'id_periodo_letivo', 'id_periodo_letivo');
    }
}
