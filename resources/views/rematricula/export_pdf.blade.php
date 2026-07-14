@extends('layouts.print')
@section('titulo', 'Relatório de Rematrícula')
@section('subtitulo', 'Relatório de Rematrícula')

@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $money = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
@endphp

@section('conteudo')
    <h1>Relatório de Rematrícula</h1>
    <p class="sub">
        @if ($pAnterior && $pProximo)
            {{ $pAnterior->descricao }} → {{ $pProximo->descricao }}
        @endif
    </p>

    <div class="cards">
        <div class="kpi"><div class="l">Ativos no anterior</div><div class="v">{{ $n($resumo['ativos_anterior']) }}</div></div>
        <div class="kpi"><div class="l">Poss. formandos</div><div class="v">{{ $n($resumo['formandos']) }}</div></div>
        <div class="kpi"><div class="l">Base rematrícula</div><div class="v">{{ $n($resumo['base_remat']) }}</div></div>
        <div class="kpi"><div class="l">Rematriculados</div><div class="v">{{ $n($resumo['rematriculados']) }}</div></div>
        <div class="kpi"><div class="l">Não rematricularam</div><div class="v">{{ $n($resumo['nao_rematriculou']) }}</div></div>
        <div class="kpi"><div class="l">Novos alunos</div><div class="v">{{ $n($resumo['novos_alunos'] ?? 0) }}</div></div>
        <div class="kpi"><div class="l">Taxa</div><div class="v">{{ $resumo['taxa'] }}%</div></div>
        <div class="kpi"><div class="l">Inadimplentes</div><div class="v">{{ $n($resumo['alunos_inadimplentes']) }}</div></div>
        <div class="kpi"><div class="l">Valor inadimplente</div><div class="v" style="font-size:14px;">{{ $money($resumo['valor_inadimplente']) }}</div></div>
    </div>

    @foreach (['Por Curso' => [$porCurso, false], 'Por Turma' => [$porTurma, true]] as $titulo => $cfg)
        @php [$rows, $temTurma] = $cfg; @endphp
        <h2>{{ $titulo }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ $temTurma ? 'Turma' : 'Curso' }}</th>
                    @if ($temTurma) <th>Curso</th> @endif
                    @foreach ($statusCols as $s) <th class="num">{{ $s }}</th> @endforeach
                    <th class="num">Novos</th>
                    <th class="num">Formandos</th>
                    <th class="num">Base</th>
                    <th class="num">Inadimpl.</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td>{{ $temTurma ? ($r['turma'] ?? '') : ($r['curso'] ?? '') }}</td>
                        @if ($temTurma) <td>{{ $r['curso'] ?? '' }}</td> @endif
                        @foreach ($statusCols as $s) <td class="num">{{ $n($r['status'][$s] ?? 0) }}</td> @endforeach
                        <td class="num">{{ $n($r['novos'] ?? 0) }}</td>
                        <td class="num">{{ $n($r['formandos'] ?? 0) }}</td>
                        <td class="num">{{ $n($r['base_remat'] ?? 0) }}</td>
                        <td class="num">{{ $n($r['inadimpl'] ?? 0) }}</td>
                        <td class="num">{{ $n($r['total'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endsection
