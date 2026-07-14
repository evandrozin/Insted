@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuário' : 'Novo usuário')

@push('head')
<style>
    .perm-grupo { border: 1px solid var(--line); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    .perm-grupo h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin: 0 0 10px; }
    .perm-item { display: flex; align-items: flex-start; gap: 9px; padding: 6px 0; font-size: 13.5px; }
    .perm-item input { margin-top: 2px; }
    #perm-box.desativado { opacity: .45; pointer-events: none; }
</style>
@endpush

@section('conteudo')
<div class="card" style="max-width:720px;">
    <div class="card-h"><h2>{{ $usuario->exists ? 'Editar usuário' : 'Novo usuário' }}</h2></div>
    <div class="card-b">
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
            @csrf
            @if ($usuario->exists) @method('PUT') @endif

            <div class="grid cols-2">
                <div class="field">
                    <label>Nome</label>
                    <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required>
                </div>
                <div class="field">
                    <label>E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                </div>
            </div>

            <div class="field">
                <label>Senha {{ $usuario->exists ? '(deixe em branco para manter)' : '' }}</label>
                <input type="password" name="password" {{ $usuario->exists ? '' : 'required' }} autocomplete="new-password">
                <div class="hint">Mínimo de 8 caracteres.</div>
            </div>

            <div class="field" style="border-top:1px solid var(--line);padding-top:16px;">
                <label class="perm-item" style="font-size:14px;">
                    <input type="checkbox" name="is_admin" value="1" id="chk-admin"
                        @checked(old('is_admin', $usuario->is_admin)) onchange="toggleAdmin()">
                    <span><strong>Administrador</strong> — acesso total a todas as funcionalidades</span>
                </label>
            </div>

            <div id="perm-box">
                <p class="muted" style="margin:0 0 12px;font-size:13px;">Selecione as funcionalidades liberadas para este usuário:</p>
                @foreach ($grupos as $grupo => $perms)
                    <div class="perm-grupo">
                        <h3>{{ $grupo }}</h3>
                        @foreach ($perms as $chave => $rotulo)
                            <label class="perm-item">
                                <input type="checkbox" name="permissions[]" value="{{ $chave }}"
                                    @checked(in_array($chave, old('permissions', $usuario->permissions ?? []), true))>
                                <span>{{ $rotulo }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="page-actions" style="margin-top:8px;">
                <button class="btn primary" type="submit">Salvar</button>
                <a class="btn ghost" href="{{ route('usuarios.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAdmin() {
        var box = document.getElementById('perm-box');
        box.classList.toggle('desativado', document.getElementById('chk-admin').checked);
    }
    toggleAdmin();
</script>
@endsection
