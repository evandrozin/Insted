<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoLetivo extends Model
{
    protected $table = 'periodos_letivos';
    protected $primaryKey = 'id_periodo_letivo';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id_periodo_letivo', 'id_org', 'org_descricao', 'descricao',
        'descricao_especial', 'data_inicio', 'data_termino', 'situacao',
        'ano', 'semestre', 'periodo_atual', 'tipo', 'consolidado',
        'raw', 'sincronizado_em',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_termino' => 'date',
        'raw' => 'array',
        'sincronizado_em' => 'datetime',
    ];

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'id_periodo_letivo', 'id_periodo_letivo');
    }
}
