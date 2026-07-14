<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngestaoLog extends Model
{
    protected $table = 'ingestao_logs';

    protected $guarded = [];

    protected $casts = [
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
        'total_registros' => 'integer',
        'total_paginas' => 'integer',
    ];

    public function marcarConcluido(int $total, int $paginas, ?string $msg = null): void
    {
        $this->update([
            'status' => 'concluido',
            'total_registros' => $total,
            'total_paginas' => $paginas,
            'mensagem' => $msg,
            'finalizado_em' => now(),
        ]);
    }

    public function marcarErro(string $msg): void
    {
        $this->update([
            'status' => 'erro',
            'mensagem' => $msg,
            'finalizado_em' => now(),
        ]);
    }
}
