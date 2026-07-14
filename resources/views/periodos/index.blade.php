@extends('layouts.app')
@section('titulo', 'Períodos Letivos')

@section('conteudo')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <p class="muted" style="margin:0;">Anos e períodos disponíveis, importados do JACAD.</p>
        <form method="POST" action="{{ route('periodos.sincronizar') }}">
            @csrf
            <button class="btn primary" type="submit">⟳ Atualizar períodos</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ano</th>
                        <th>Descrição</th>
                        <th>Semestre</th>
                        <th>Início</th>
                        <th>Término</th>
                        <th>Situação</th>
                        <th>Matrículas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periodos as $p)
                        <tr>
                            <td><strong>{{ $p->ano }}</strong></td>
                            <td>{{ $p->descricao }}</td>
                            <td>{{ $p->semestre }}</td>
                            <td>{{ optional($p->data_inicio)->format('d/m/Y') }}</td>
                            <td>{{ optional($p->data_termino)->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $p->periodo_atual ? 'ok' : 'mut' }}">{{ $p->situacao ?: ($p->periodo_atual ? 'Atual' : '—') }}</span></td>
                            <td><a href="{{ route('matriculas.index', ['ano' => $p->ano]) }}">{{ number_format($p->matriculas_count, 0, ',', '.') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty"><div class="big">◷</div>Nenhum período letivo. Clique em <strong>Atualizar períodos</strong>.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($periodos->hasPages())
            <div class="card-b">{{ $periodos->links() }}</div>
        @endif
    </div>
@endsection
