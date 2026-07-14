<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · Insted</title>
    <style>
        :root {
            --teal: #17BEB8; --teal-dark: #119c97; --teal-soft: #e6f8f7;
            --graphite: #2C2F36; --ink: #1f2126; --muted: #6b7280;
            --line: #e6e8ec; --bg: #f4f6f8; --card: #ffffff; --danger: #e5484d;
            --shadow: 0 1px 2px rgba(16,24,40,.06), 0 10px 30px rgba(16,24,40,.10);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #2C2F36 0%, #119c97 140%);
            color: var(--ink); font-size: 14px; padding: 24px;
        }
        .box { width: 100%; max-width: 380px; background: var(--card); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; }
        .box .top { padding: 28px 28px 6px; text-align: center; }
        .logo { width: 46px; height: 46px; border-radius: 12px; background: var(--teal); color: #06403e; display: grid; place-items: center; font-weight: 800; font-size: 22px; margin: 0 auto 12px; }
        .top .name { font-weight: 700; font-size: 20px; color: var(--graphite); }
        .top .name b { color: var(--teal-dark); }
        .top .sub { display: block; font-size: 10px; color: var(--muted); letter-spacing: 2px; font-weight: 600; margin-top: 2px; }
        form { padding: 22px 28px 28px; }
        .field { margin-bottom: 15px; }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--graphite); margin-bottom: 6px; }
        .field input { width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 9px; font-size: 14px; font-family: inherit; }
        .field input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-soft); }
        .remember { display: flex; align-items: center; gap: 7px; font-size: 13px; color: var(--muted); margin-bottom: 18px; }
        .btn { width: 100%; padding: 11px; border: none; border-radius: 9px; background: var(--teal); color: #06403e; font-weight: 700; font-size: 14px; cursor: pointer; transition: .12s; }
        .btn:hover { background: var(--teal-dark); color: #fff; }
        .alert { background: #fdeaea; border: 1px solid #f3c2c3; color: #a12327; padding: 10px 13px; border-radius: 9px; font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="top">
            <div style="display:flex;justify-content:center;margin-bottom:8px;">
                <svg viewBox="0 0 100 100" width="60" height="60" fill="none" xmlns="http://www.w3.org/2000/svg" style="color:var(--teal-dark);">
                    <g stroke="currentColor" stroke-width="6">
                        <ellipse cx="50" cy="50" rx="42" ry="17"/>
                        <ellipse cx="50" cy="50" rx="42" ry="17" transform="rotate(60 50 50)"/>
                        <ellipse cx="50" cy="50" rx="42" ry="17" transform="rotate(120 50 50)"/>
                    </g>
                </svg>
            </div>
            <div class="name" style="font-size:26px;">insted<span style="color:var(--teal);">.</span></div>
            <span class="sub">CENTRO UNIVERSITÁRIO</span>
        </div>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            <div class="field">
                <label>E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus required>
            </div>
            <div class="field">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1"> Manter conectado
            </label>
            <button class="btn" type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
