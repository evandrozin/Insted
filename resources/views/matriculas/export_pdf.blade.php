@extends('layouts.print')
@section('titulo', 'Relatório de Matrículas')
@section('subtitulo', 'Relatório de Matrículas')

@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp

@section('conteudo')
    <h1>Relatório de Matrículas</h1>
    <p class="sub">Total no contexto: <strong>{{ $n($totalContexto) }}</strong> matrícula(s).</p>

    <div class="cards">
        @foreach ($porStatus as $st => $qtd)
            <div class="kpi">
                <div class="l">{{ $st ?: 'Sem status' }}</div>
                <div class="v">{{ $n($qtd) }}</div>
            </div>
        @endforeach
    </div>

    @foreach (['Sintético por Curso' => [$sinteticoCurso, $sinteticoCursoTotais, false], 'Sintético por Turma' => [$sintetico, $sinteticoTotais, true]] as $titulo => $cfg)
        @php [$rows, $totais, $temExtra] = $cfg; @endphp
        <h2>{{ $titulo }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ $temExtra ? 'Turma' : 'Curso' }}</th>
                    @if ($temExtra) <th>Curso</th> @endif
                    @foreach ($statusColunas as $s) <th class="num">{{ $s }}</th> @endforeach
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td>{{ $r['label'] }}</td>
                        @if ($temExtra) <td>{{ $r['extra'] }}</td> @endif
                        @foreach ($statusColunas as $s) <td class="num">{{ $n($r['status'][$s] ?? 0) }}</td> @endforeach
                        <td class="num">{{ $n($r['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    @if ($temExtra) <td></td> @endif
                    @foreach ($statusColunas as $s) <td class="num">{{ $n($totais[$s] ?? 0) }}</td> @endforeach
                    <td class="num">{{ $n($totais['total'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @endforeach
@endsection
