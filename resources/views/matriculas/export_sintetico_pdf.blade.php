@extends('layouts.print')
@section('titulo', $titulo)
@section('subtitulo', $titulo)

@php $n = fn ($v) => number_format((int) $v, 0, ',', '.'); $nDim = count($dimensoes); @endphp

@section('conteudo')
    <h1>{{ $titulo }}</h1>
    <p class="sub">{{ $n(count($rows)) }} linha(s).</p>

    <table>
        <thead>
            <tr>
                @foreach ($dimensoes as $d)<th>{{ $d['label'] }}</th>@endforeach
                @foreach ($statusColunas as $st)<th class="num">{{ $st }}</th>@endforeach
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $linha)
                <tr>
                    @foreach ($dimensoes as $d)<td>{{ $linha['dims'][$d['alias']] }}</td>@endforeach
                    @foreach ($statusColunas as $st)<td class="num">{{ $n($linha['status'][$st] ?? 0) }}</td>@endforeach
                    <td class="num">{{ $n($linha['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                @for ($i = 1; $i < $nDim; $i++)<td></td>@endfor
                @foreach ($statusColunas as $st)<td class="num">{{ $n($totais[$st] ?? 0) }}</td>@endforeach
                <td class="num">{{ $n($totais['total'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
