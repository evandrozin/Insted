<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Exibe o formulário de login. */
    public function mostrarLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /** Autentica o usuário. */
    public function login(Request $request)
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $lembrar = $request->boolean('remember');

        if (! Auth::attempt($credenciais, $lembrar)) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $request->session()->regenerate();

        // Registra o acesso (para a área de logs de login). O registro é
        // acessório: uma falha aqui não pode impedir o login.
        try {
            $usuario = Auth::user();
            LoginLog::create([
                'user_id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'session_id' => $request->session()->getId(),
                'logged_in_at' => now(),
                'last_activity_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->intended(route('dashboard'));
    }

    /** Encerra a sessão. */
    public function logout(Request $request)
    {
        // Carimba a saída no log de acesso desta sessão (antes de invalidá-la).
        try {
            DB::table('login_logs')
                ->where('session_id', $request->session()->getId())
                ->whereNull('logged_out_at')
                ->update([
                    'logged_out_at' => now(),
                    'last_activity_at' => now(),
                    'logout_type' => 'manual',
                ]);
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
