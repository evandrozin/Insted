@extends('layouts.app')
@section('titulo', 'Usuários')

@section('conteudo')
    <div class="card">
        <div class="card-h">
            <h2>{{ number_format($usuarios->total(), 0, ',', '.') }} usuário(s)</h2>
            <div class="page-actions">
                <a class="btn primary" href="{{ route('usuarios.create') }}">+ Novo usuário</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Permissões</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        <tr>
                            <td><strong>{{ $u->name }}</strong></td>
                            <td class="mono">{{ $u->email }}</td>
                            <td>
                                @if ($u->is_admin)
                                    <span class="badge info">Administrador</span>
                                @else
                                    <span class="badge mut">Usuário</span>
                                @endif
                            </td>
                            <td>
                                @if ($u->is_admin)
                                    <span class="muted">Acesso total</span>
                                @elseif (empty($u->permissions))
                                    <span class="muted">— nenhuma —</span>
                                @else
                                    <span class="muted">{{ count($u->permissions) }} liberada(s)</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a class="btn ghost" href="{{ route('usuarios.edit', $u) }}">Editar</a>
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('usuarios.destroy', $u) }}" style="display:inline;"
                                          onsubmit="return confirm('Remover o usuário {{ $u->name }}?');">
                                        @csrf @method('DELETE')
                                        <button class="btn ghost" type="submit" style="color:var(--danger);">Remover</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Nenhum usuário cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $usuarios->links() }}
@endsection
