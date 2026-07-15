<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_logs';

    protected $guarded = [];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    /** Minutos de inatividade a partir dos quais a sessão é considerada perdida/expirada. */
    public const JANELA_ONLINE_MIN = 5;

    /**
     * Situação derivada da sessão:
     *  - 'encerrada'      → o usuário clicou em sair (logged_out_at preenchido)
     *  - 'online'         → sem logout e com atividade recente
     *  - 'perdida'        → sem logout, mas sem atividade recente (fechou o navegador/caiu a conexão)
     */
    public function situacao(): string
    {
        if ($this->logged_out_at) {
            return 'encerrada';
        }

        $ultima = $this->last_activity_at ?? $this->logged_in_at;

        if ($ultima && $ultima->gt(now()->subMinutes(self::JANELA_ONLINE_MIN))) {
            return 'online';
        }

        return 'perdida';
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
