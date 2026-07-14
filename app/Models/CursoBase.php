<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoBase extends Model
{
    protected $table = 'cursos_base';
    protected $primaryKey = 'id_curso_base';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'raw' => 'array',
        'sincronizado_em' => 'datetime',
    ];
}
