<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Painel') · Insted</title>
    <style>
        :root {
            --teal: #17BEB8;
            --teal-dark: #119c97;
            --teal-soft: #e6f8f7;
            --graphite: #2C2F36;
            --graphite-2: #3a3e47;
            --ink: #1f2126;
            --muted: #6b7280;
            --line: #e6e8ec;
            --bg: #f4f6f8;
            --card: #ffffff;
            --danger: #e5484d;
            --warn: #f5a623;
            --ok: #17a34a;
            --radius: 12px;
            --shadow: 0 1px 2px rgba(16,24,40,.06), 0 4px 16px rgba(16,24,40,.05);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            font-size: 14px;
            line-height: 1.5;
        }
        a { color: var(--teal-dark); text-decoration: none; }

        .layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 248px; flex-shrink: 0;
            background: var(--graphite);
            color: #cfd3da;
            display: flex; flex-direction: column;
            position: sticky; top: 0; height: 100vh;
        }
        .brand {
            display: flex; align-items: center; gap: 10px;
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand .logo {
            width: 34px; height: 34px; border-radius: 9px;
            background: var(--teal);
            display: grid; place-items: center;
            color: #06403e; font-weight: 800; font-size: 17px;
        }
        .brand .name { color: #fff; font-weight: 700; font-size: 16px; letter-spacing: .3px; }
        .brand .name b { color: var(--teal); }
        .brand .sub { display:block; font-size: 10px; color: #8b909a; letter-spacing: 1.5px; font-weight: 600; }

        .nav { padding: 12px 10px; flex: 1; overflow-y: auto; }
        .nav .group { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #767c88; margin: 14px 12px 6px; }
        .nav a {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 12px; border-radius: 9px;
            color: #cfd3da; font-weight: 500; margin-bottom: 2px;
            transition: background .12s, color .12s;
        }
        .nav a .ico { width: 18px; text-align: center; opacity: .9; }
        .nav a:hover { background: rgba(255,255,255,.06); color: #fff; }
        .nav a.active { background: var(--teal); color: #06403e; font-weight: 600; }
        .sidebar .foot { padding: 14px 18px; border-top: 1px solid rgba(255,255,255,.08); font-size: 11.5px; color: #767c88; }

        /* Main */
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            height: 62px; background: var(--card);
            border-bottom: 1px solid var(--line);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 26px; position: sticky; top: 0; z-index: 5;
        }
        .topbar h1 { font-size: 17px; margin: 0; font-weight: 700; color: var(--graphite); }
        .topbar .user { display: flex; align-items: center; gap: 9px; color: var(--muted); font-size: 13px; }
        .topbar .avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--teal-soft); color: var(--teal-dark); display: grid; place-items: center; font-weight: 700; }

        .content { padding: 26px; max-width: 1280px; width: 100%; }

        /* Cards / tables / buttons */
        .card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
        .card .card-h { padding: 16px 20px; border-bottom: 1px solid var(--line); display:flex; align-items:center; justify-content:space-between; }
        .card .card-h h2 { font-size: 15px; margin: 0; color: var(--graphite); }
        .card .card-b { padding: 20px; }

        .grid { display: grid; gap: 16px; }
        .grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
        .grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
        @media (max-width: 900px){ .grid.cols-4,.grid.cols-3,.grid.cols-2 { grid-template-columns: 1fr 1fr; } .sidebar{ display:none; } }

        .stat { padding: 18px 20px; }
        .stat .label { color: var(--muted); font-size: 12.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
        .stat .value { font-size: 30px; font-weight: 800; color: var(--graphite); margin-top: 6px; }
        .stat .value small { font-size: 13px; font-weight: 600; color: var(--muted); }
        .stat .ico { float: right; width: 40px; height: 40px; border-radius: 10px; background: var(--teal-soft); color: var(--teal-dark); display: grid; place-items: center; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 11.5px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); padding: 11px 14px; border-bottom: 1px solid var(--line); background: #fafbfc; position: sticky; top: 0; }
        tbody td { padding: 11px 14px; border-bottom: 1px solid var(--line); font-size: 13px; }
        tbody tr:hover { background: #fafbfc; }
        .table-wrap { overflow-x: auto; }

        .badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
        .badge.ok { background: #e7f6ec; color: var(--ok); }
        .badge.warn { background: #fdf1dd; color: #b9770e; }
        .badge.err { background: #fdeaea; color: var(--danger); }
        .badge.info { background: var(--teal-soft); color: var(--teal-dark); }
        .badge.mut { background: #eef0f3; color: var(--muted); }

        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 9px; border: 1px solid transparent; font-weight: 600; font-size: 13px; cursor: pointer; transition: .12s; }
        .btn.primary { background: var(--teal); color: #06403e; }
        .btn.primary:hover { background: var(--teal-dark); color: #fff; }
        .btn.ghost { background: #fff; border-color: var(--line); color: var(--graphite); }
        .btn.ghost:hover { border-color: var(--teal); color: var(--teal-dark); }
        .btn.dark { background: var(--graphite); color: #fff; }
        .btn.dark:hover { background: var(--graphite-2); }

        .field { margin-bottom: 15px; }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--graphite); margin-bottom: 6px; }
        .field input, .field select {
            width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 9px;
            font-size: 13.5px; font-family: inherit; background: #fff;
        }
        .field input:focus, .field select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-soft); }
        .field .hint { font-size: 11.5px; color: var(--muted); margin-top: 4px; }

        .filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .filters .field { margin-bottom: 0; min-width: 160px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 13.5px; border: 1px solid; }
        .alert.success { background: #e7f6ec; border-color: #b7e3c4; color: #146c34; }
        .alert.error { background: #fdeaea; border-color: #f3c2c3; color: #a12327; }
        .alert.info { background: var(--teal-soft); border-color: #b7e8e5; color: #0c6f6b; }

        .muted { color: var(--muted); }
        .mono { font-family: 'Cascadia Code', Consolas, monospace; font-size: 12.5px; }
        .page-actions { display: flex; gap: 10px; }
        .pagination { display: flex; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 11px; border: 1px solid var(--line); border-radius: 8px; background: #fff; font-size: 13px; color: var(--graphite); }
        .pagination .active span { background: var(--teal); color: #06403e; border-color: var(--teal); font-weight: 700; }
        .pagination .disabled span { color: #c3c7cd; }
        .empty { text-align: center; padding: 48px 20px; color: var(--muted); }
        .empty .big { font-size: 34px; margin-bottom: 8px; }
    </style>
    @stack('head')
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">i</div>
            <div>
                <span class="name">inst<b>ed</b></span>
                <span class="sub">CENTRO UNIVERSITÁRIO</span>
            </div>
        </div>
        <nav class="nav">
            <div class="group">Visão Geral</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ico">◧</span> Dashboard</a>

            @canany([\App\Support\Permissions::MATRICULAS_VER, \App\Support\Permissions::REMATRICULA_VER])
                <div class="group">Acadêmico</div>
                @can(\App\Support\Permissions::MATRICULAS_VER)
                    <a href="{{ route('matriculas.index') }}" class="{{ request()->routeIs('matriculas.*') ? 'active' : '' }}"><span class="ico">▤</span> Matrículas</a>
                @endcan
                @can(\App\Support\Permissions::REMATRICULA_VER)
                    <a href="{{ route('rematricula.index') }}" class="{{ request()->routeIs('rematricula.*') ? 'active' : '' }}"><span class="ico">⇄</span> Rematrícula</a>
                @endcan
                <a href="{{ route('periodos.index') }}" class="{{ request()->routeIs('periodos.*') ? 'active' : '' }}"><span class="ico">◷</span> Períodos Letivos</a>
            @endcanany

            @canany([\App\Support\Permissions::DADOS_SINCRONIZAR, \App\Support\Permissions::PARAMETROS_GERENCIAR])
                <div class="group">Integração</div>
                @can(\App\Support\Permissions::DADOS_SINCRONIZAR)
                    <a href="{{ route('ingestao.index') }}" class="{{ request()->routeIs('ingestao.*') ? 'active' : '' }}"><span class="ico">⟳</span> Sincronização</a>
                @endcan
                @can(\App\Support\Permissions::PARAMETROS_GERENCIAR)
                    <a href="{{ route('parametros.index') }}" class="{{ request()->routeIs('parametros.*') ? 'active' : '' }}"><span class="ico">⚙</span> Parâmetros de API</a>
                @endcan
            @endcanany

            @can(\App\Support\Permissions::USUARIOS_GERENCIAR)
                <div class="group">Administração</div>
                <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}"><span class="ico">◐</span> Usuários</a>
            @endcan
        </nav>
        <div class="foot">Insted · Integração JACAD<br>v1.0</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <h1>@yield('titulo', 'Painel')</h1>
            @auth
                <div class="user">
                    <span>{{ auth()->user()->name }}{{ auth()->user()->is_admin ? ' · Admin' : '' }}</span>
                    <div class="avatar">{{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn ghost" style="padding:6px 12px;">Sair</button>
                    </form>
                </div>
            @endauth
        </header>
        <main class="content">
            @if (session('sucesso'))
                <div class="alert success">{{ session('sucesso') }}</div>
            @endif
            @if (session('erro'))
                <div class="alert error">{{ session('erro') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif
            @yield('conteudo')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
