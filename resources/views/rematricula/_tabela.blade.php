{{--
    Tabela de coorte de rematrícula, com cabeçalho agrupador (anterior x próximo).
    Params: $titulo, $visao, $dimensoes, $rows, $totais, $statusCols,
            $statusCor, $corHex, $labelAnterior, $labelProximo, $idAnterior, $idProximo
--}}
@php
    $idCols = count($dimensoes);                   // Unidade, Curso [, Turma]
    $antCols = 5;                                  // Ativos, Formandos, Adimpl, Inadimpl, Vlr aberto
    $proxCols = count($statusCols) + 2;            // status + Novos + % Remat
@endphp
<div class="card rmt-tabela" style="margin-top:22px;">
    <div class="card-h">
        <h2>{{ $titulo }}</h2>
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="muted" style="font-size:12.5px;">{{ count($rows) }} linha(s) · formandos fora da base</span>
            <div class="page-actions">
                <a class="btn ghost" href="{{ route('rematricula.exportar', ['visao' => $visao, 'formato' => 'excel', 'anterior' => $idAnterior, 'proximo' => $idProximo]) }}">⬇ Excel</a>
                <a class="btn ghost" href="{{ route('rematricula.exportar', ['visao' => $visao, 'formato' => 'pdf', 'anterior' => $idAnterior, 'proximo' => $idProximo]) }}" target="_blank">⬇ PDF</a>
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th colspan="{{ $idCols }}" style="border-bottom:1px solid var(--line);"></th>
                    <th colspan="{{ $antCols }}" style="text-align:center;background:#eef6f6;color:#0c6f6b;border-left:2px solid #bfe3e1;border-right:2px solid #bfe3e1;">
                        Período anterior · {{ $labelAnterior }}
                    </th>
                    <th colspan="{{ $proxCols }}" style="text-align:center;background:#f2f0ff;color:#5b45c9;">
                        Próximo (rematrícula) · {{ $labelProximo }}
                    </th>
                </tr>
                <tr>
                    @foreach ($dimensoes as $d)
                        <th>{{ $d['label'] }}</th>
                    @endforeach
                    <th class="num" style="border-left:2px solid #bfe3e1;">Ativos</th>
                    <th class="num" style="color:#7c5cff;">Poss. form.</th>
                    <th class="num" style="color:#17a34a;">Adimpl.</th>
                    <th class="num" style="color:#e5484d;">Inadimpl.</th>
                    <th class="num" style="color:#b9770e;border-right:2px solid #bfe3e1;">Vlr. aberto</th>
                    @foreach ($statusCols as $st)
                        <th class="num" style="color:{{ $corHex[$statusCor($st)] }};">{{ $st }}</th>
                    @endforeach
                    <th class="num" style="color:#0ea5e9;" title="Novos: alunos ATIVA/AGUARDANDO no próximo período que não estavam ativos no anterior (ingressantes + retornantes).">Novos</th>
                    <th class="num">% Remat.</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $linha)
                    @php
                        $base = $linha['base_remat'] ?? 0;
                        $nao = $linha['status']['NÃO REMATRICULOU'] ?? 0;
                        $remat = $base - $nao;
                        $taxa = $base ? round($remat / $base * 100) : 0;
                    @endphp
                    <tr>
                        @foreach ($dimensoes as $i => $d)
                            <td @class(['muted' => $i > 0])>{{ $linha[$d['alias']] ?? '' }}</td>
                        @endforeach
                        <td class="num" style="border-left:2px solid #eef0f3;"><strong>{{ number_format($linha['total'], 0, ',', '.') }}</strong></td>
                        <td class="num" style="color:#7c5cff;">{{ ($linha['formandos'] ?? 0) ? number_format($linha['formandos'], 0, ',', '.') : '·' }}</td>
                        <td class="num">{{ ($linha['adimpl'] ?? 0) ? number_format($linha['adimpl'], 0, ',', '.') : '·' }}</td>
                        <td class="num" style="color:#e5484d;">{{ ($linha['inadimpl'] ?? 0) ? number_format($linha['inadimpl'], 0, ',', '.') : '·' }}</td>
                        <td class="num muted" style="border-right:2px solid #eef0f3;">{{ ($linha['valor_inad'] ?? 0) ? 'R$ '.number_format($linha['valor_inad'], 2, ',', '.') : '·' }}</td>
                        @foreach ($statusCols as $st)
                            <td class="num">{{ ($linha['status'][$st] ?? 0) ? number_format($linha['status'][$st], 0, ',', '.') : '·' }}</td>
                        @endforeach
                        <td class="num" style="color:#0ea5e9;font-weight:600;">{{ ($linha['novos'] ?? 0) ? number_format($linha['novos'], 0, ',', '.') : '·' }}</td>
                        <td class="num"><span class="badge {{ $taxa >= 70 ? 'ok' : ($taxa >= 40 ? 'warn' : 'err') }}">{{ $taxa }}%</span></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $idCols + $antCols + $proxCols }}"><div class="empty">Sem dados.</div></td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                @php
                    $baseT = $totais['base_remat'] ?? 0;
                    $naoT = $totais['NÃO REMATRICULOU'] ?? 0;
                    $rematT = $baseT - $naoT;
                    $taxaT = $baseT ? round($rematT / $baseT * 100) : 0;
                @endphp
                <tfoot>
                    <tr style="background:#fafbfc;font-weight:700;">
                        <td>TOTAL</td>
                        @for ($i = 1; $i < $idCols; $i++)<td></td>@endfor
                        <td class="num" style="border-left:2px solid #eef0f3;">{{ number_format($totais['total'] ?? 0, 0, ',', '.') }}</td>
                        <td class="num" style="color:#7c5cff;">{{ number_format($totais['formandos'] ?? 0, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totais['adimpl'] ?? 0, 0, ',', '.') }}</td>
                        <td class="num" style="color:#e5484d;">{{ number_format($totais['inadimpl'] ?? 0, 0, ',', '.') }}</td>
                        <td class="num" style="border-right:2px solid #eef0f3;">R$ {{ number_format($totais['valor_inad'] ?? 0, 2, ',', '.') }}</td>
                        @foreach ($statusCols as $st)
                            <td class="num">{{ number_format($totais[$st] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="num" style="color:#0ea5e9;">{{ number_format($totais['novos'] ?? 0, 0, ',', '.') }}</td>
                        <td class="num">{{ $taxaT }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
