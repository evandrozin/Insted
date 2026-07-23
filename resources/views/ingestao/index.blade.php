@extends('layouts.app')
@section('titulo', 'Sincronização')

@section('conteudo')
    <div class="grid cols-2" style="margin-bottom:22px;">
        <div class="card">
            <div class="card-h"><h2>Importar matrículas do JACAD</h2></div>
            <div class="card-b">
                <p class="muted" style="margin-top:0;">Busca os períodos letivos e, para cada um, todas as matrículas (paginação por cursor) gravando no Postgres. Deixe o ano em branco para importar <strong>todos os anos</strong>.</p>
                <form method="POST" action="{{ route('ingestao.sincronizar') }}">
                    @csrf
                    <div class="filters">
                        <div class="field">
                            <label>Ano (opcional)</label>
                            <select name="ano">
                                <option value="">Todos os anos</option>
                                @foreach ($anos as $ano)
                                    <option value="{{ $ano }}">{{ $ano }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn primary" type="submit" onclick="this.innerHTML='⟳ Sincronizando...';this.disabled=true;this.form.submit();">⟳ Sincronizar agora</button>
                    </div>
                </form>
                <div class="alert info" style="margin-top:16px;margin-bottom:0;">
                    Para grandes volumes, prefira o terminal:
                    <div class="mono" style="margin-top:6px;">php artisan jacad:sync-matriculas</div>
                    <div class="mono">php artisan jacad:sync-matriculas --ano=2025</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Conexão</h2></div>
            <div class="card-b">
                <p class="muted" style="margin-top:0;">Testa a autenticação com a API JACAD usando os parâmetros ativos.</p>
                <form method="POST" action="{{ route('ingestao.testar') }}">
                    @csrf
                    <button class="btn dark" type="submit">⇄ Testar conexão</button>
                </form>
                <hr style="border:0;border-top:1px solid var(--line);margin:18px 0;">
                <p class="muted" style="margin-top:0;">Importa cidades e perfis (endereço do aluno) — base do painel de <a href="{{ route('demografia.index') }}">Demografia</a>.</p>
                <form method="POST" action="{{ route('ingestao.sincronizar-demografia') }}">
                    @csrf
                    <button class="btn ghost" type="submit" onclick="this.innerHTML='◍ Importando...';this.disabled=true;this.form.submit();">◍ Sincronizar endereços</button>
                </form>
                <div class="alert info" style="margin-top:16px;margin-bottom:0;font-size:12.5px;">
                    <strong>Atenção:</strong> o token JACAD tem restrição por IP. O IP do servidor onde este sistema roda precisa estar autorizado no painel JACAD (Integrações → API de Integrações → Tokens de Acesso).
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Histórico de sincronizações</h2></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Referência</th>
                        <th>Status</th>
                        <th>Registros</th>
                        <th>Páginas</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $map = ['concluido'=>'ok','erro'=>'err','executando'=>'warn']; @endphp
                        <tr>
                            <td class="mono">{{ $log->id }}</td>
                            <td>{{ $log->tipo }}</td>
                            <td>{{ $log->referencia }}</td>
                            <td><span class="badge {{ $map[$log->status] ?? 'mut' }}">{{ ucfirst($log->status) }}</span></td>
                            <td>{{ number_format($log->total_registros, 0, ',', '.') }}</td>
                            <td>{{ $log->total_paginas }}</td>
                            <td>{{ optional($log->iniciado_em)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($log->finalizado_em)->format('d/m/Y H:i') }}</td>
                            <td class="muted" style="max-width:260px;">{{ \Illuminate\Support\Str::limit($log->mensagem, 120) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="empty"><div class="big">⟳</div>Nenhuma sincronização executada ainda.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-b">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
