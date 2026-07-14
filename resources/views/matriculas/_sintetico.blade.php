{{--
    Sintético (pivot) reutilizável.
    Params: $titulo, $rows, $statusColunas, $totais, $filtroParam, $labelCol,
            $mostrarExtra (bool), $extraCol, $statusCor, $corHex
--}}
<div class="card" style="margin-top:22px;">
    <div class="card-h">
        <h2>{{ $titulo }}</h2>
        <span class="muted" style="font-size:12.5px;">{{ count($rows) }} {{ \Illuminate\Support\Str::plural(strtolower($labelCol), count($rows)) }}{{ count($rows) >= 300 ? ' (top 300)' : '' }} · respeita os filtros</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>{{ $labelCol }}</th>
                    @if ($mostrarExtra)<th>{{ $extraCol }}</th>@endif
                    @foreach ($statusColunas as $st)
                        <th style="text-align:right;color:{{ $corHex[$statusCor($st)] }};">{{ $st }}</th>
                    @endforeach
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $linha)
                    <tr>
                        <td>
                            @if ($linha['id'])
                                <a href="{{ request()->fullUrlWithQuery([$filtroParam => $linha['id'], 'status' => null, 'page' => null]) }}">{{ $linha['label'] }}</a>
                            @else
                                {{ $linha['label'] }}
                            @endif
                        </td>
                        @if ($mostrarExtra)<td class="muted">{{ $linha['extra'] }}</td>@endif
                        @foreach ($statusColunas as $st)
                            <td style="text-align:right;">{{ ($linha['status'][$st] ?? 0) ? number_format($linha['status'][$st], 0, ',', '.') : '·' }}</td>
                        @endforeach
                        <td style="text-align:right;"><strong>{{ number_format($linha['total'], 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($statusColunas) + ($mostrarExtra ? 3 : 2) }}"><div class="empty"><div class="big">▤</div>Sem dados para o filtro atual.</div></td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                <tfoot>
                    <tr style="background:#fafbfc;font-weight:700;">
                        <td>TOTAL</td>
                        @if ($mostrarExtra)<td></td>@endif
                        @foreach ($statusColunas as $st)
                            <td style="text-align:right;">{{ number_format($totais[$st] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right;">{{ number_format($totais['total'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
