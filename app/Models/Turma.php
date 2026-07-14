<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    protected $table = 'turmas';
    protected $primaryKey = 'id_turma';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'raw' => 'array',
        'sincronizado_em' => 'datetime',
    ];
}
