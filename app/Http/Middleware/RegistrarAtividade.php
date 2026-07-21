<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Heartbeat de atividade: em cada requisição autenticada, atualiza
 * o last_activity_at do log de acesso da sessão atual. Serve para
 * saber quais sessões continuam ativas e detectar conexões perdidas.
 *
 * Faz no máximo uma escrita por minuto por sessão (a cláusula WHERE
 * limita a linhas cuja última atividade já passou de 60s).
 */
class RegistrarAtividade
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->hasSession()) {
            // O heartbeat é acessório: qualquer falha aqui (tabela ausente,
            // indisponibilidade do banco) NÃO pode derrubar a navegação.
            try {
                DB::table('login_logs')
                    ->where('session_id', $request->session()->getId())
                    ->whereNull('logged_out_at')
                    ->where(function ($q) {
                        $q->whereNull('last_activity_at')
                            ->orWhere('last_activity_at', '<=', now()->subSeconds(60));
                    })
                    ->update(['last_activity_at' => now()]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
