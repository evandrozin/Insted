<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matriz extends Model
{
    protected $table = 'matrizes';
    protected $primaryKey = 'id_curso_matriz';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'raw' => 'array',
        'sincronizado_em' => 'datetime',
    ];
}
