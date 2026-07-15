@extends('layouts.app')
@section('titulo', 'Logs de acesso')

@php
    $situacaoBadge = [
        'online' => ['ok', '● Online'],
        'perdida' => ['warn', 'Conexão perdida'],
        'encerrada' => ['mut', 'Encerrada'],
    ];
    $fmt = fn ($d) => $d ? $d->format('d/m/Y H:i:s') : '—';
@endphp

@push('head')
<style>
    .status-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 18px; }
    .status-card {
        background: var(--card); border: 1px solid var(--line); border-left: 4px solid var(--c);
        border-radius: 10px; padding: 12px 14px; box-shadow: var(--shadow);
        display: flex; flex-direction: column; gap: 3px;
    }
    .status-card .sc-label { font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--muted); }
    .status-card .sc-value { font-size: 22px; font-weight: 800; color: var(--graphite); }
</style>
@endpush

@section('conteudo')
    <div class="status-cards">
        <div class="status-card" style="--c:#17a34a;">
            <span class="sc-label">Online agora</span>
            <span class="sc-value">{{ number_format($online, 0, ',', '.') }}</span>
        </div>
        <div class="status-card" style="--c:#119c97;">
            <span class="sc-label">Logins hoje</span>
            <span class="sc-value">{{ number_format($hoje, 0, ',', '.') }}</span>
        </div>
        <div class="status-card" style="--c:#6b7280;">
            <span class="sc-label">Total de acessos</span>
            <span class="sc-value">{{ number_format($total, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <div class="card-b">
            <form method="GET" action="{{ route('logs-acesso.index') }}" class="filters">
                <div class="field">
                    <label>Busca (nome, e-mail, IP)</label>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Digite para buscar...">
                </div>
                <div class="field">
                    <label>Situação</label>
                    <select name="situacao">
                        <option value="">Todas</option>
                        <option value="online" @selected(request('situacao') === 'online')>Online</option>
                        <option value="perdida" @selected(request('situacao') === 'perdida')>Conexão perdida / expirada</option>
                        <option value="encerrada" @selected(request('situacao') === 'encerrada')>Encerrada (saiu)</option>
                    </select>
                </div>
                <div class="page-actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn ghost" href="{{ route('logs-acesso.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <h2>{{ number_format($logs->total(), 0, ',', '.') }} acesso(s)</h2>
            <span class="muted" style="font-size:12px;">Sessão considerada perdida após {{ \App\Models\LoginLog::JANELA_ONLINE_MIN }} min sem atividade.</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>IP</th>
                        <th>Entrada</th>
                        <th>Última atividade</th>
                        <th>Saída</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php [$cor, $rotulo] = $situacaoBadge[$log->situacao()]; @endphp
                        <tr>
                            <td>
                                <strong>{{ $log->name ?: '—' }}</strong>
                                <div class="muted" style="font-size:11.5px;">{{ $log->email }}</div>
                            </td>
                            <td class="mono">{{ $log->ip ?: '—' }}</td>
                            <td>{{ $fmt($log->logged_in_at) }}</td>
                            <td>{{ $fmt($log->last_activity_at) }}</td>
                            <td>{{ $log->logged_out_at ? $fmt($log->logged_out_at) : '—' }}</td>
                            <td><span class="badge {{ $cor }}">{{ $rotulo }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><div class="big">◔</div>Nenhum acesso registrado ainda.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-b">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
