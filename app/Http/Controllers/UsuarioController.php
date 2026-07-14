<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->paginate(25);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $usuario = new User;
        $grupos = Permissions::agrupadas();

        return view('usuarios.form', compact('usuario', 'grupos'));
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);

        User::create([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => $dados['password'],
            'is_admin' => $dados['is_admin'],
            'permissions' => $dados['permissions'],
        ]);

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário criado com sucesso.');
    }

    public function edit(User $usuario)
    {
        $grupos = Permissions::agrupadas();

        return view('usuarios.form', compact('usuario', 'grupos'));
    }

    public function update(Request $request, User $usuario)
    {
        $dados = $this->validar($request, $usuario);

        $usuario->name = $dados['name'];
        $usuario->email = $dados['email'];
        $usuario->is_admin = $dados['is_admin'];
        $usuario->permissions = $dados['permissions'];
        if (! empty($dados['password'])) {
            $usuario->password = $dados['password'];
        }
        $usuario->save();

        return redirect()->route('usuarios.index')->with('sucesso', 'Usuário atualizado.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('erro', 'Você não pode remover o próprio usuário.');
        }

        $usuario->delete();

        return back()->with('sucesso', 'Usuário removido.');
    }

    /**
     * Valida e normaliza os dados do formulário.
     *
     * @return array{name:string,email:string,password:?string,is_admin:bool,permissions:list<string>}
     */
    protected function validar(Request $request, ?User $usuario = null): array
    {
        $senhaObrigatoria = $usuario === null ? 'required' : 'nullable';

        $validado = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$senhaObrigatoria, 'string', 'min:8'],
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permissions::todas())],
        ]);

        $isAdmin = $request->boolean('is_admin');

        return [
            'name' => $validado['name'],
            'email' => $validado['email'],
            'password' => $validado['password'] ?? null,
            'is_admin' => $isAdmin,
            // Admin não precisa de permissões individuais (tem todas via Gate::before).
            'permissions' => $isAdmin ? [] : array_values($validado['permissions'] ?? []),
        ];
    }
}
