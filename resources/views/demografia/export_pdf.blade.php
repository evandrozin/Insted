@extends('layouts.print')
@section('titulo', 'Demografia dos Alunos')
@section('subtitulo', 'Demografia dos Alunos')

@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $pct = fn ($v) => number_format((float) $v, 1, ',', '.').'%';
    $cobertura = fn ($sem) => $totalAlunos > 0 ? round(($totalAlunos - $sem) / $totalAlunos * 100, 1) : 0;
@endphp

@push('head')
<style>
    .pizza { display: flex; gap: 14px; align-items: flex-start; }
    .pizza svg { flex: none; }
    .pizza .legenda { list-style: none; margin: 0; padding: 0; flex: 1; }
    .pizza .legenda li {
        display: grid; grid-template-columns: 9px 1fr auto auto; gap: 7px;
        align-items: center; padding: 2px 0; font-size: 9.5px; border-bottom: 1px solid var(--line);
    }
    .pizza .legenda li:last-child { border-bottom: 0; }
    .pizza .dot { width: 9px; height: 9px; border-radius: 2px; }
    .pizza .rot { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pizza .pct { font-weight: 700; }
    .pizza .qtd { color: var(--muted); min-width: 36px; text-align: right; }

    /* Empilhados, não lado a lado: em largura de A4 duas colunas espremem a
       legenda a ~117px e os nomes de bairro saem cortados por reticências. */
    .graficos > div { margin-bottom: 10px; page-break-inside: avoid; }
    .filtros { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 11px; }
    .filtros b { color: var(--graphite); }
    .filtros span + span { margin-left: 18px; }

    /* Cabeçalho repete a cada página; linha não parte no meio. */
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .quebra { page-break-before: always; }
</style>
@endpush

@section('conteudo')
    <h1>Demografia dos Alunos</h1>
    <p class="sub">Distribuição por cidade e bairro — contagem por aluno distinto.</p>

    <div class="filtros">
        @foreach ($filtros as $rotulo => $valor)
            <span><b>{{ $rotulo }}:</b> {{ $valor }}</span>
        @endforeach
    </div>

    @if ($totalAlunos === 0)
        <p class="sub">Nenhum aluno encontrado para os filtros selecionados.</p>
    @else
        <div class="cards">
            <div class="kpi"><div class="l">Alunos</div><div class="v">{{ $n($totalAlunos) }}</div></div>
            <div class="kpi"><div class="l">Cidades</div><div class="v">{{ $n(count($linhasCidade)) }}</div></div>
            <div class="kpi"><div class="l">Bairros</div><div class="v">{{ $n(count($linhasBairro)) }}</div></div>
            <div class="kpi">
                <div class="l">Com cidade</div>
                <div class="v">{{ $pct($cobertura($semCidade)) }}</div>
            </div>
            <div class="kpi">
                <div class="l">Com bairro</div>
                <div class="v">{{ $pct($cobertura($semEndereco)) }}</div>
            </div>
        </div>

        <div class="graficos">
            <div>
                <h2>Por Cidade</h2>
                @include('demografia._pizza', ['fatias' => $cidades, 'total' => $totalAlunos])
            </div>
            <div>
                <h2>Por Bairro</h2>
                @include('demografia._pizza', ['fatias' => $bairros, 'total' => $totalAlunos])
            </div>
        </div>

        <h2>Detalhamento por Cidade</h2>
        <table>
            <thead>
                <tr><th>Cidade</th><th class="num">Alunos</th><th class="num">%</th></tr>
            </thead>
            <tbody>
                @foreach ($linhasCidade as $l)
                    <tr>
                        <td>{{ $l['rotulo'] }}</td>
                        <td class="num">{{ $n($l['total']) }}</td>
                        <td class="num">{{ $pct($l['pct']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td>Total</td><td class="num">{{ $n($totalAlunos) }}</td><td class="num">100,0%</td></tr>
            </tfoot>
        </table>

        <h2 class="quebra">Detalhamento por Bairro</h2>
        <table>
            <thead>
                <tr><th>Bairro</th><th class="num">Alunos</th><th class="num">%</th></tr>
            </thead>
            <tbody>
                @foreach ($linhasBairro as $l)
                    <tr>
                        <td>{{ $l['rotulo'] }}</td>
                        <td class="num">{{ $n($l['total']) }}</td>
                        <td class="num">{{ $pct($l['pct']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td>Total</td><td class="num">{{ $n($totalAlunos) }}</td><td class="num">100,0%</td></tr>
            </tfoot>
        </table>
    @endif
@endsection
