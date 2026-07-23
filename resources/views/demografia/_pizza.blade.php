{{--
    Gráfico de pizza em SVG puro (sem dependências).
    Espera:
      $fatias — list<array{rotulo, total, pct, cor}>
      $total  — total geral, usado para calcular os ângulos
--}}
@php
    $r = 100;      // raio
    $c = 110;      // centro (x e y)
    $acumulado = 0;
    $arcos = [];

    foreach ($fatias as $f) {
        if ($f['total'] <= 0) {
            continue;
        }

        $inicio = $acumulado / max($total, 1) * 2 * M_PI - M_PI / 2;
        $acumulado += $f['total'];
        $fim = $acumulado / max($total, 1) * 2 * M_PI - M_PI / 2;

        $arcos[] = [
            'cor' => $f['cor'],
            'rotulo' => $f['rotulo'],
            'pct' => $f['pct'],
            'total' => $f['total'],
            'x1' => round($c + $r * cos($inicio), 3),
            'y1' => round($c + $r * sin($inicio), 3),
            'x2' => round($c + $r * cos($fim), 3),
            'y2' => round($c + $r * sin($fim), 3),
            'maior' => ($fim - $inicio) > M_PI ? 1 : 0,
        ];
    }

    // Uma fatia só (100%) vira um círculo — o arco degeneraria em nada.
    $fatiaUnica = count($arcos) === 1;
@endphp

<div class="pizza">
    <svg viewBox="0 0 220 220" width="220" height="220" role="img" aria-label="Distribuição percentual">
        @if ($fatiaUnica)
            <circle cx="{{ $c }}" cy="{{ $c }}" r="{{ $r }}" fill="{{ $arcos[0]['cor'] }}" stroke="#fff" stroke-width="1.5">
                <title>{{ $arcos[0]['rotulo'] }} — {{ number_format($arcos[0]['total'], 0, ',', '.') }} ({{ number_format($arcos[0]['pct'], 1, ',', '.') }}%)</title>
            </circle>
        @else
            @foreach ($arcos as $a)
                <path d="M {{ $c }} {{ $c }} L {{ $a['x1'] }} {{ $a['y1'] }} A {{ $r }} {{ $r }} 0 {{ $a['maior'] }} 1 {{ $a['x2'] }} {{ $a['y2'] }} Z"
                      fill="{{ $a['cor'] }}" stroke="#fff" stroke-width="1.5">
                    <title>{{ $a['rotulo'] }} — {{ number_format($a['total'], 0, ',', '.') }} ({{ number_format($a['pct'], 1, ',', '.') }}%)</title>
                </path>
            @endforeach
        @endif
    </svg>

    <ul class="legenda">
        @foreach ($fatias as $f)
            <li>
                <span class="dot" style="background: {{ $f['cor'] }};"></span>
                <span class="rot" title="{{ $f['rotulo'] }}">{{ $f['rotulo'] }}</span>
                <span class="pct">{{ number_format($f['pct'], 1, ',', '.') }}%</span>
                <span class="qtd">{{ number_format($f['total'], 0, ',', '.') }}</span>
            </li>
        @endforeach
    </ul>
</div>
