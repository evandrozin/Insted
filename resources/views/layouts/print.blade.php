<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Relatório') · Insted</title>
    <style>
        :root { --teal:#17BEB8; --teal-dark:#119c97; --graphite:#2C2F36; --ink:#1f2126; --muted:#6b7280; --line:#e6e8ec; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--ink); font-size: 12px; margin: 0; padding: 28px; }
        .rep-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--teal); padding-bottom: 12px; margin-bottom: 18px; }
        .rep-head .brand { display: flex; align-items: center; gap: 10px; }
        .rep-head .logo { width: 32px; height: 32px; border-radius: 8px; background: var(--teal); color: #06403e; display: grid; place-items: center; font-weight: 800; font-size: 16px; }
        .rep-head .name { font-weight: 700; font-size: 16px; color: var(--graphite); }
        .rep-head .name b { color: var(--teal-dark); }
        .rep-head .meta { text-align: right; font-size: 11px; color: var(--muted); }
        h1 { font-size: 16px; margin: 0 0 4px; color: var(--graphite); }
        h2 { font-size: 13px; margin: 22px 0 8px; color: var(--graphite); }
        .sub { color: var(--muted); font-size: 11.5px; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid var(--line); padding: 5px 8px; font-size: 11px; text-align: left; }
        th { background: #f4f6f8; text-transform: uppercase; letter-spacing: .3px; font-size: 10px; color: var(--muted); }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: 700; background: #fafbfc; }
        .cards { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; }
        .kpi { border: 1px solid var(--line); border-left: 3px solid var(--teal); border-radius: 8px; padding: 8px 12px; min-width: 130px; }
        .kpi .l { font-size: 10px; text-transform: uppercase; color: var(--muted); font-weight: 700; }
        .kpi .v { font-size: 18px; font-weight: 800; color: var(--graphite); }
        .toolbar { margin-bottom: 16px; }
        .btn { display: inline-block; padding: 8px 14px; border-radius: 8px; background: var(--teal); color: #06403e; font-weight: 700; font-size: 12px; border: none; cursor: pointer; text-decoration: none; }
        @media print { .toolbar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" onclick="window.print()">🖨 Imprimir / Salvar em PDF</button>
    </div>
    <div class="rep-head">
        <div class="brand">
            <div class="logo">i</div>
            <div class="name">inst<b>ed</b></div>
        </div>
        <div class="meta">
            @yield('subtitulo')<br>
            Gerado em {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
    @yield('conteudo')
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
