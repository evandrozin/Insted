@extends('layouts.app')
@section('titulo', 'Demografia')

@push('head')
<style>
    .pizza { display: flex; gap: 22px; align-items: center; flex-wrap: wrap; }
    .pizza svg { flex-shrink: 0; }
    .pizza .legenda { list-style: none; margin: 0; padding: 0; flex: 1; min-width: 240px; }
    .pizza .legenda li {
        display: grid; grid-template-columns: 12px 1fr auto auto; gap: 9px;
        align-items: center; padding: 4px 0; font-size: 12.5px; border-bottom: 1px solid var(--line);
    }
    .pizza .legenda li:last-child { border-bottom: 0; }
    .pizza .dot { width: 12px; height: 12px; border-radius: 3px; }
    .pizza .rot { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pizza .pct { font-weight: 700; color: var(--graphite); }
    .pizza .qtd { color: var(--muted); min-width: 46px; text-align: right; }
    .barra { height: 6px; border-radius: 999px; background: #eef0f3; overflow: hidden; }
    .barra > span { display: block; height: 100%; background: var(--teal); }
</style>
@endpush

@section('conteudo')
    <div class="card" style="margin-bottom:18px;">
        <div class="card-b">
            <form method="GET" action="{{ route('demografia.index') }}" class="filters">
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
                <div class="page-actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn ghost" href="{{ route('demografia.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    @if (! $temPerfis)
        <div class="alert info">
            Os dados de endereço ainda não foram importados. Rode a sincronização de perfis para popular cidade e bairro:
            <div class="mono" style="margin-top:6px;">php artisan jacad:sync-matriculas --somente-perfis</div>
        </div>
    @endif

    @if ($totalAlunos === 0)
        <div class="card"><div class="empty"><div class="big">◍</div>Nenhum aluno encontrado para os filtros selecionados.</div></div>
    @else
        <div class="grid cols-3" style="margin-bottom:18px;">
            <div class="card stat">
                <span class="ico">◍</span>
                <div class="label">Alunos no filtro</div>
                <div class="value">{{ number_format($totalAlunos, 0, ',', '.') }}</div>
            </div>
            <div class="card stat">
                <span class="ico">⌖</span>
                <div class="label">Com cidade informada</div>
                <div class="value">
                    {{ number_format($totalAlunos - $semCidade, 0, ',', '.') }}
                    <small>de {{ number_format($totalAlunos, 0, ',', '.') }}</small>
                </div>
                <div class="barra" style="margin-top:8px;"><span style="width: {{ $totalAlunos ? round(($totalAlunos - $semCidade) / $totalAlunos * 100, 1) : 0 }}%;"></span></div>
            </div>
            <div class="card stat">
                <span class="ico">⌂</span>
                <div class="label">Com bairro informado</div>
                <div class="value">
                    {{ number_format($totalAlunos - $semEndereco, 0, ',', '.') }}
                    <small>de {{ number_format($totalAlunos, 0, ',', '.') }}</small>
                </div>
                <div class="barra" style="margin-top:8px;"><span style="width: {{ $totalAlunos ? round(($totalAlunos - $semEndereco) / $totalAlunos * 100, 1) : 0 }}%;"></span></div>
            </div>
        </div>

        <div class="grid cols-2">
            <div class="card">
                <div class="card-h">
                    <h2>Alunos por Cidade</h2>
                    <span class="muted" style="font-size:12px;">{{ count($linhasCidade) }} cidade(s)</span>
                </div>
                <div class="card-b">
                    @include('demografia._pizza', ['fatias' => $cidades, 'total' => $totalAlunos])
                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <h2>Alunos por Bairro</h2>
                    <span class="muted" style="font-size:12px;">{{ count($linhasBairro) }} bairro(s)</span>
                </div>
                <div class="card-b">
                    @include('demografia._pizza', ['fatias' => $bairros, 'total' => $totalAlunos])
                </div>
            </div>
        </div>

        <div class="grid cols-2" style="margin-top:18px;">
            @foreach ([['Cidades', $linhasCidade, 'Cidade'], ['Bairros', $linhasBairro, 'Bairro']] as [$titulo, $linhas, $coluna])
                <div class="card">
                    <div class="card-h"><h2>Detalhamento — {{ $titulo }}</h2></div>
                    <div class="table-wrap" style="max-height:420px;overflow-y:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ $coluna }}</th>
                                    <th style="text-align:right;">Alunos</th>
                                    <th style="text-align:right;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($linhas as $l)
                                    <tr>
                                        <td>{{ $l['rotulo'] }}</td>
                                        <td style="text-align:right;">{{ number_format($l['total'], 0, ',', '.') }}</td>
                                        <td style="text-align:right;font-weight:600;">{{ number_format($l['pct'], 1, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
