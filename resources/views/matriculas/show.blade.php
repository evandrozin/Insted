@extends('layouts.app')
@section('titulo', 'Matrícula #'.$matricula->id_matricula)

@section('conteudo')
    <a href="{{ route('matriculas.index') }}" class="btn ghost" style="margin-bottom:16px;">← Voltar</a>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-h"><h2>{{ $matricula->aluno }}</h2></div>
            <div class="card-b">
                @php
                    $campos = [
                        'RA' => $matricula->ra,
                        'RA Estadual' => $matricula->ra_estadual,
                        'E-mail' => $matricula->aluno_email,
                        'E-mail institucional' => $matricula->aluno_email_institucional,
                        'Curso' => $matricula->curso,
                        'Matriz' => $matricula->matriz,
                        'Turma' => $matricula->turma,
                        'Período Letivo' => $matricula->periodo_letivo,
                        'Status' => $matricula->status,
                        'Unidade Física' => $matricula->unidade_fisica,
                        'Organização' => $matricula->organizacao,
                    ];
                @endphp
                <table>
                    @foreach ($campos as $rot => $val)
                        <tr>
                            <td class="muted" style="width:180px;">{{ $rot }}</td>
                            <td><strong>{{ $val ?: '—' }}</strong></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Datas & IDs</h2></div>
            <div class="card-b">
                <table>
                    <tr><td class="muted">Data matrícula</td><td>{{ optional($matricula->data_matricula)->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><td class="muted">Data ativação</td><td>{{ optional($matricula->data_ativacao)->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><td class="muted">Data trancamento</td><td>{{ optional($matricula->data_trancamento)->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><td class="muted">Data cadastro</td><td>{{ optional($matricula->data_cadastro)->format('d/m/Y') ?: '—' }}</td></tr>
                    <tr><td class="muted">ID Matrícula</td><td class="mono">{{ $matricula->id_matricula }}</td></tr>
                    <tr><td class="muted">ID Aluno</td><td class="mono">{{ $matricula->id_aluno }}</td></tr>
                    <tr><td class="muted">ID Turma</td><td class="mono">{{ $matricula->id_turma }}</td></tr>
                    <tr><td class="muted">Sincronizado em</td><td>{{ optional($matricula->sincronizado_em)->format('d/m/Y H:i') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
@endsection
