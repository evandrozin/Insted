@extends('layouts.app')
@section('titulo', 'Parâmetros de API')

@section('conteudo')
    <p class="muted" style="margin-top:0;margin-bottom:16px;">Configurações das integrações. Estes valores são a fonte de verdade em runtime (base URL, usuário e token de acesso).</p>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Integração</th>
                        <th>Base URL</th>
                        <th>Usuário</th>
                        <th>Token</th>
                        <th>Page size</th>
                        <th>Status</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parametros as $p)
                        <tr>
                            <td><strong>{{ $p->nome }}</strong><div class="muted" style="font-size:11.5px;">{{ $p->descricao }}</div></td>
                            <td class="mono">{{ $p->base_url }}</td>
                            <td>{{ $p->usuario }}</td>
                            <td class="mono">{{ $p->token ? \Illuminate\Support\Str::mask($p->token, '•', 4, max(strlen($p->token)-8,0)) : '—' }}</td>
                            <td>{{ $p->page_size }}</td>
                            <td><span class="badge {{ $p->ativo ? 'ok' : 'mut' }}">{{ $p->ativo ? 'Ativo' : 'Inativo' }}</span></td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="{{ route('parametros.edit', $p) }}" class="btn ghost">Editar</a>
                                <form method="POST" action="{{ route('parametros.testar', $p) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn dark" type="submit">Testar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><div class="big">⚙</div>Nenhum parâmetro cadastrado. Rode <span class="mono">php artisan db:seed</span>.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
