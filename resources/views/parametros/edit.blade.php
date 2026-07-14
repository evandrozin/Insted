@extends('layouts.app')
@section('titulo', 'Editar Parâmetro · '.$parametro->nome)

@section('conteudo')
    <a href="{{ route('parametros.index') }}" class="btn ghost" style="margin-bottom:16px;">← Voltar</a>

    <div class="card" style="max-width:640px;">
        <div class="card-h"><h2>Integração: {{ $parametro->nome }}</h2></div>
        <div class="card-b">
            <form method="POST" action="{{ route('parametros.update', $parametro) }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label>Descrição</label>
                    <input type="text" name="descricao" value="{{ old('descricao', $parametro->descricao) }}">
                </div>
                <div class="field">
                    <label>Base URL *</label>
                    <input type="url" name="base_url" value="{{ old('base_url', $parametro->base_url) }}" required>
                    <div class="hint">Ex.: https://insted-developer.jacad.com.br</div>
                </div>
                <div class="field">
                    <label>Usuário</label>
                    <input type="text" name="usuario" value="{{ old('usuario', $parametro->usuario) }}">
                </div>
                <div class="field">
                    <label>Token (Chave de API)</label>
                    <input type="text" name="token" value="{{ old('token', $parametro->token) }}" class="mono">
                    <div class="hint">Gerado no painel JACAD. Lembre-se da restrição por IP.</div>
                </div>
                <div class="field">
                    <label>Page size *</label>
                    <input type="number" name="page_size" value="{{ old('page_size', $parametro->page_size) }}" min="1" max="1000" required>
                </div>
                <div class="field">
                    <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                        <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $parametro->ativo)) style="width:auto;">
                        Integração ativa
                    </label>
                </div>

                <div class="page-actions" style="margin-top:8px;">
                    <button class="btn primary" type="submit">Salvar</button>
                    <a class="btn ghost" href="{{ route('parametros.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
