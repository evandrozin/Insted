{{--
    Sintético (pivot) por dimensões (Unidade → Curso [→ Turma]).
    Params: $titulo, $visao, $dimensoes, $rows, $totais, $statusColunas, $statusCor, $corHex
--}}
@php $nDim = count($dimensoes); @endphp
<div class="card" style="margin-top:22px;">
    <div class="card-h">
        <h2>{{ $titulo }}</h2>
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="muted" style="font-size:12.5px;">{{ count($rows) }} linha(s) · respeita os filtros</span>
            <div class="page-actions">
                <a class="btn ghost" href="{{ route('matriculas.exportar', array_merge(['visao' => $visao, 'formato' => 'excel'], request()->query())) }}">⬇ Excel</a>
                <a class="btn ghost" href="{{ route('matriculas.exportar', array_merge(['visao' => $visao, 'formato' => 'pdf'], request()->query())) }}" target="_blank">⬇ PDF</a>
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    @foreach ($dimensoes as $d)
                        <th>{{ $d['label'] }}</th>
                    @endforeach
                    @foreach ($statusColunas as $st)
                        <th style="text-align:right;color:{{ $corHex[$statusCor($st)] }};">{{ $st }}</th>
                    @endforeach
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $linha)
                    <tr>
                        @foreach ($dimensoes as $d)
                            @php $val = $linha['dims'][$d['alias']]; $id = $linha['ids'][$d['alias']] ?? null; @endphp
                            <td>
                                @if (! empty($d['filtro']) && $id)
                                    <a href="{{ request()->fullUrlWithQuery([$d['filtro'] => $id, 'status' => null, 'page' => null]) }}">{{ $val }}</a>
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                        @endforeach
                        @foreach ($statusColunas as $st)
                            <td style="text-align:right;">{{ ($linha['status'][$st] ?? 0) ? number_format($linha['status'][$st], 0, ',', '.') : '·' }}</td>
                        @endforeach
                        <td style="text-align:right;"><strong>{{ number_format($linha['total'], 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $nDim + count($statusColunas) + 1 }}"><div class="empty"><div class="big">▤</div>Sem dados para o filtro atual.</div></td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                <tfoot>
                    <tr style="background:#fafbfc;font-weight:700;">
                        <td>TOTAL</td>
                        @for ($i = 1; $i < $nDim; $i++)<td></td>@endfor
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
