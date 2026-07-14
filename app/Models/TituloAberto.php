<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TituloAberto extends Model
{
    protected $table = 'titulos_abertos';
    protected $primaryKey = 'id_transacao';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'data_vencimento' => 'date',
        'valor' => 'decimal:2',
        'sincronizado_em' => 'datetime',
    ];
}
