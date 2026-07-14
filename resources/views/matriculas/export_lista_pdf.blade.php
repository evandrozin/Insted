@extends('layouts.print')
@section('titulo', 'Matrículas')
@section('subtitulo', 'Lista de Matrículas')

@section('conteudo')
    <h1>Lista de Matrículas</h1>
    <p class="sub">{{ number_format($rows->count(), 0, ',', '.') }} matrícula(s){{ $rows->count() >= 5000 ? ' (limitado a 5.000 — refine os filtros)' : '' }}.</p>

    <table>
        <thead>
            <tr>
                @foreach ($colunas as $c)<th>{{ $c }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $m)
                <tr>
                    <td>{{ $m->unidade_fisica }}</td>
                    <td>{{ $m->curso }}</td>
                    <td>{{ $m->turma }}</td>
                    <td>{{ $m->ra }}</td>
                    <td>{{ $m->aluno }}</td>
                    <td>{{ $m->aluno_email }}</td>
                    <td>{{ $m->periodo_letivo }}</td>
                    <td>{{ $m->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
