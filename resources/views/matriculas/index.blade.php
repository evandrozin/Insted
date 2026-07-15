@extends('layouts.app')
@section('titulo', 'Matrículas')

@php
    // Cor do card/badge conforme o status.
    $statusCor = function (?string $s) {
        $s = strtolower($s ?? '');
        if (str_contains($s, 'ativ') || str_contains($s, 'aprov')) return 'ok';
        if (str_contains($s, 'tranc') || str_contains($s, 'cancel') || str_contains($s, 'reprov') || str_contains($s, 'desist') || str_contains($s, 'infreq')) return 'err';
        if (str_contains($s, 'aguard') || str_contains($s, 'remanej') || str_contains($s, 'reenquad') || str_contains($s, 'transf')) return 'warn';
        return 'mut';
    };
    $corHex = ['ok' => '#17a34a', 'err' => '#e5484d', 'warn' => '#b9770e', 'mut' => '#6b7280', 'info' => '#119c97'];
@endphp

@push('head')
<style>
    .status-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px; }
    .status-card {
        background: var(--card); border: 1px solid var(--line); border-left: 4px solid var(--c);
        border-radius: 10px; padding: 12px 14px; box-shadow: var(--shadow);
        display: flex; flex-direction: column; gap: 3px; transition: .12s; color: var(--ink);
    }
    .status-card:hover { border-color: var(--c); transform: translateY(-1px); }
    .status-card.ativo { box-shadow: 0 0 0 2px var(--c) inset, var(--shadow); }
    .status-card .sc-label { font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--muted); }
    .status-card .sc-value { font-size: 22px; font-weight: 800; color: var(--graphite); }
</style>
@endpush

@section('conteudo')
    {{-- Totalizadores por status (respeitam os filtros; clique para filtrar) --}}
    <div class="status-cards">
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
           class="status-card {{ request('status') ? '' : 'ativo' }}" style="--c:#119c97;">
            <span class="sc-label">Total</span>
            <span class="sc-value">{{ number_format($totalContexto, 0, ',', '.') }}</span>
        </a>
        @foreach ($porStatus as $st => $qtd)
            @php $c = $corHex[$statusCor($st)]; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => $st, 'page' => null]) }}"
               class="status-card {{ request('status') === (string) $st ? 'ativo' : '' }}" style="--c:{{ $c }};">
                <span class="sc-label">{{ $st ?: '—' }}</span>
                <span class="sc-value">{{ number_format($qtd, 0, ',', '.') }}</span>
            </a>
        @endforeach
    </div>

    <div class="card" style="margin-bottom:18px;">
        <div class="card-b">
            <form method="GET" action="{{ route('matriculas.index') }}" class="filters">
                {{-- preserva o status selecionado pelos cards ao filtrar --}}
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="field">
                    <label>Busca (nome, RA, e-mail)</label>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Digite para buscar...">
                </div>
                <div class="field">
                    <label>Período Letivo</label>
                    <select name="periodo">
                        <option value="">Todos</option>
                        @foreach ($periodos as $p)
                            <option value="{{ $p->id_periodo_letivo }}" @selected(request('periodo') == $p->id_periodo_letivo)>{{ $p->descricao }} · {{ \Illuminate\Support\Str::of($p->org_descricao)->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Curso</label>
                    <select name="curso">
                        <option value="">Todos</option>
                        @foreach ($cursos as $c)
                            <option value="{{ $c->id_curso_base }}" @selected(request('curso') == $c->id_curso_base)>{{ $c->nome_impressao }}{{ $c->modalidade ? ' - '.$c->modalidade : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Turma</label>
                    <select name="turma">
                        <option value="">Todas</option>
                        @foreach ($turmas as $t)
                            <option value="{{ $t->id_turma }}" @selected(request('turma') == $t->id_turma)>{{ $t->turma }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Adimplência</label>
                    <select name="adimplencia">
                        <option value="">Todos</option>
                        <option value="adimplente" @selected(request('adimplencia') === 'adimplente')>Adimplente</option>
                        <option value="inadimplente" @selected(request('adimplencia') === 'inadimplente')>Inadimplente</option>
                    </select>
                </div>
                <div class="page-actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn ghost" href="{{ route('matriculas.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- 1) Sintético por Unidade → Curso --}}
    @include('matriculas._sintetico', [
        'titulo' => 'Sintético por Curso',
        'visao' => 'curso',
        'dimensoes' => $dimCurso,
        'rows' => $sinteticoCurso,
        'totais' => $sinteticoCursoTotais,
        'statusColunas' => $statusColunas,
        'statusCor' => $statusCor,
        'corHex' => $corHex,
    ])

    {{-- 2) Sintético por Unidade → Curso → Turma --}}
    @include('matriculas._sintetico', [
        'titulo' => 'Sintético por Turma',
        'visao' => 'turma',
        'dimensoes' => $dimTurma,
        'rows' => $sintetico,
        'totais' => $sinteticoTotais,
        'statusColunas' => $statusColunas,
        'statusCor' => $statusCor,
        'corHex' => $corHex,
    ])

    {{-- 3) Relação de alunos (lista detalhada) --}}
    <div class="card" style="margin-top:22px;">
        <div class="card-h">
            <h2>{{ number_format($matriculas->total(), 0, ',', '.') }} matrícula(s)</h2>
            <div class="page-actions">
                <a class="btn ghost" href="{{ route('matriculas.exportar', array_merge(['visao' => 'lista', 'formato' => 'excel'], request()->query())) }}">⬇ Excel</a>
                <a class="btn ghost" href="{{ route('matriculas.exportar', array_merge(['visao' => 'lista', 'formato' => 'pdf'], request()->query())) }}" target="_blank">⬇ PDF</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Unidade</th>
                        <th>Curso</th>
                        <th>Turma</th>
                        <th>RA</th>
                        <th>Aluno</th>
                        <th>Período</th>
                        <th>Status</th>
                        <th>Adimplência</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matriculas as $m)
                        <tr>
                            <td>{{ $m->unidade_fisica }}</td>
                            <td>{{ $m->curso }}</td>
                            <td>{{ $m->turma }}</td>
                            <td class="mono">{{ $m->ra }}</td>
                            <td>
                                <a href="{{ route('matriculas.show', $m->id_matricula) }}">{{ $m->aluno }}</a>
                                <div class="muted" style="font-size:11.5px;">{{ $m->aluno_email }}</div>
                            </td>
                            <td>{{ $m->periodo_letivo }}</td>
                            <td><span class="badge {{ $statusCor($m->status) }}">{{ $m->status ?: '—' }}</span></td>
                            <td><span class="badge {{ $m->inadimplente ? 'err' : 'ok' }}">{{ $m->inadimplente ? 'Inadimplente' : 'Adimplente' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty"><div class="big">▤</div>Nenhuma matrícula encontrada.<br><span style="font-size:13px;">Rode a <a href="{{ route('ingestao.index') }}">sincronização</a> para importar do JACAD.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($matriculas->hasPages())
            <div class="card-b">{{ $matriculas->links() }}</div>
        @endif
    </div>
@endsection
