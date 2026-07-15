<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $limite = now()->subMinutes(LoginLog::JANELA_ONLINE_MIN);

        $query = LoginLog::query();

        // Busca por nome, e-mail ou IP.
        if ($request->filled('busca')) {
            $b = trim($request->busca);
            $query->where(function ($q) use ($b) {
                $q->where('name', 'ilike', "%{$b}%")
                    ->orWhere('email', 'ilike', "%{$b}%")
                    ->orWhere('ip', 'ilike', "%{$b}%");
            });
        }

        // Filtro por situação.
        if ($request->filled('situacao')) {
            $this->filtrarSituacao($query, $request->situacao, $limite);
        }

        $logs = $query->orderByDesc('logged_in_at')->paginate(30)->withQueryString();

        // Totalizadores (independentes dos filtros de busca/situação).
        $online = LoginLog::whereNull('logged_out_at')->where('last_activity_at', '>', $limite)->count();
        $hoje = LoginLog::whereDate('logged_in_at', today())->count();
        $total = LoginLog::count();

        return view('logs_acesso.index', compact('logs', 'online', 'hoje', 'total'));
    }

    /** Aplica o filtro de situação (online | perdida | encerrada) à query. */
    protected function filtrarSituacao($query, string $situacao, \Illuminate\Support\Carbon $limite): void
    {
        match ($situacao) {
            'online' => $query->whereNull('logged_out_at')->where('last_activity_at', '>', $limite),
            'perdida' => $query->whereNull('logged_out_at')->where(function ($q) use ($limite) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<=', $limite);
            }),
            'encerrada' => $query->whereNotNull('logged_out_at'),
            default => null,
        };
    }
}
