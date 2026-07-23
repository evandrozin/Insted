<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $table = 'perfis';
    protected $primaryKey = 'id_perfil';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'sincronizado_em' => 'datetime',
    ];

    public function cidade()
    {
        return $this->belongsTo(Cidade::class, 'id_cidade', 'id_cidade');
    }
}
