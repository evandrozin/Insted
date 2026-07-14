@extends('layouts.app')
@section('titulo', 'Cursos')

@section('conteudo')
    <div class="card" style="margin-bottom:18px;">
        <div class="card-b">
            <form method="GET" action="{{ route('cursos.index') }}" class="filters">
                <div class="field" style="min-width:240px;">
                    <label>Busca (nome, código, modalidade)</label>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Digite para buscar...">
                </div>
                <div class="field">
                    <label>Modalidade</label>
                    <select name="modalidade">
                        <option value="">Todas</option>
                        @foreach ($modalidades as $m)
                            <option value="{{ $m }}" @selected(request('modalidade') === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="page-actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn ghost" href="{{ route('cursos.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <h2>{{ number_format($cursos->total(), 0, ',', '.') }} curso(s)</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Reduzido</th>
                        <th>Código</th>
                        <th>Modalidade</th>
                        <th>Grau</th>
                        <th>Organização</th>
                        <th>Situação</th>
                        <th style="text-align:right;">Matrículas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cursos as $c)
                        <tr>
                            <td><strong>{{ $c->nome_impressao }}</strong></td>
                            <td class="muted">{{ $c->nome_reduzido }}</td>
                            <td class="mono">{{ $c->codigo_curso }}</td>
                            <td>@if ($c->modalidade)<span class="badge info">{{ $c->modalidade }}</span>@endif</td>
                            <td>{{ $c->grau_academico }}</td>
                            <td class="muted">{{ $c->org }}</td>
                            <td><span class="badge {{ strtolower($c->status ?? '') === 'ativo' ? 'ok' : 'mut' }}">{{ $c->status ?: '—' }}</span></td>
                            <td style="text-align:right;">
                                <a href="{{ route('matriculas.index', ['curso' => $c->id_curso_base]) }}">{{ number_format($c->matriculas_count, 0, ',', '.') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty"><div class="big">▤</div>Nenhum curso encontrado. Rode a sincronização.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cursos->hasPages())
            <div class="card-b">{{ $cursos->links() }}</div>
        @endif
    </div>
@endsection
