@extends('layouts.app')
@section('titulo', 'Rematrícula')

@php
    $statusCor = function (?string $s) {
        $s = strtolower($s ?? '');
        if (str_contains($s, 'não rematr') || str_contains($s, 'nao rematr')) return 'err';
        if (str_contains($s, 'ativ') || str_contains($s, 'aprov')) return 'ok';
        if (str_contains($s, 'tranc') || str_contains($s, 'cancel') || str_contains($s, 'reprov') || str_contains($s, 'desist') || str_contains($s, 'infreq')) return 'err';
        if (str_contains($s, 'aguard') || str_contains($s, 'remanej') || str_contains($s, 'reenquad') || str_contains($s, 'transf')) return 'warn';
        return 'mut';
    };
    $corHex = ['ok' => '#17a34a', 'err' => '#e5484d', 'warn' => '#b9770e', 'mut' => '#6b7280', 'info' => '#119c97'];
@endphp

@push('head')
<style>
    .rmt-tabela a { color: var(--teal-dark); }
    .rmt-tabela td.num, .rmt-tabela th.num { text-align: right; }
</style>
@endpush

@section('conteudo')
    <div class="card" style="margin-bottom:18px;">
        <div class="card-b">
            <form method="GET" action="{{ route('rematricula.index') }}" class="filters">
                <div class="field">
                    <label>Período Anterior (origem)</label>
                    <select name="anterior" required>
                        <option value="">Selecione...</option>
                        @foreach ($periodos as $p)
                            <option value="{{ $p->id_periodo_letivo }}" @selected($idAnterior == $p->id_periodo_letivo)>{{ $p->descricao }} · {{ \Illuminate\Support\Str::of($p->org_descricao)->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="align-self:flex-end;padding-bottom:9px;font-size:18px;color:var(--muted);">→</div>
                <div class="field">
                    <label>Período Próximo (destino)</label>
                    <select name="proximo" required>
                        <option value="">Selecione...</option>
                        @foreach ($periodos as $p)
                            <option value="{{ $p->id_periodo_letivo }}" @selected($idProximo == $p->id_periodo_letivo)>{{ $p->descricao }} · {{ \Illuminate\Support\Str::of($p->org_descricao)->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="page-actions">
                    <button class="btn primary" type="submit">Comparar</button>
                    <a class="btn ghost" href="{{ route('rematricula.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    @if (! $resumo)
        <div class="card"><div class="card-b">
            <div class="empty"><div class="big">⇄</div>
                Selecione o <strong>período anterior</strong> e o <strong>próximo</strong> para comparar a rematrícula.<br>
                <span style="font-size:13px;">A coorte considera os alunos <strong>ATIVOS</strong> no período anterior e mostra em qual <strong>status</strong> eles estão no próximo período, agrupado por curso e turma.</span>
            </div>
        </div></div>
    @else
        <div class="card" style="margin-bottom:16px;">
            <div class="card-b" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <span class="muted" style="font-size:13px;"><strong>Exportar</strong> este comparativo de rematrícula:</span>
                <div class="page-actions">
                    <a class="btn primary" href="{{ route('rematricula.exportar.excel', ['anterior' => $idAnterior, 'proximo' => $idProximo]) }}">⬇ Excel (CSV)</a>
                    <a class="btn dark" href="{{ route('rematricula.exportar.pdf', ['anterior' => $idAnterior, 'proximo' => $idProximo]) }}" target="_blank">⬇ PDF</a>
                </div>
            </div>
        </div>

        {{-- Funil / resumo --}}
        <div class="status-cards" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));display:grid;gap:12px;margin-bottom:18px;">
            @php
                $cards = [
                    ['Ativos no anterior', number_format($resumo['ativos_anterior'],0,',','.'), '#119c97'],
                    ['Poss. formandos', number_format($resumo['formandos'],0,',','.'), '#7c5cff'],
                    ['Base rematrícula', number_format($resumo['base_remat'],0,',','.'), '#119c97'],
                    ['Rematriculados', number_format($resumo['rematriculados'],0,',','.'), '#17a34a'],
                    ['Não rematricularam', number_format($resumo['nao_rematriculou'],0,',','.'), '#e5484d'],
                    ['Novos alunos (próximo)', number_format($resumo['novos_alunos'] ?? 0,0,',','.'), '#0ea5e9'],
                    ['Taxa de rematrícula', $resumo['taxa'].'%', '#2C2F36'],
                    ['Adimplentes (ant.)', number_format($resumo['adimplentes'],0,',','.'), '#17a34a'],
                    ['Inadimplentes (ant.)', number_format($resumo['inadimplentes'],0,',','.'), '#e5484d'],
                    ['Valor em aberto', 'R$ '.number_format($resumo['valor_inadimplente'],2,',','.'), '#b9770e'],
                ];
            @endphp
            @foreach ($cards as [$lab, $val, $cor])
                <div class="card" style="border-left:4px solid {{ $cor }};padding:14px 16px;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);">{{ $lab }}</div>
                    <div style="font-size:24px;font-weight:800;color:var(--graphite);margin-top:4px;">{{ $val }}</div>
                </div>
            @endforeach
        </div>

        <div class="alert info" style="margin-bottom:18px;">
            Comparando <strong>{{ $pAnterior->descricao ?? '' }}</strong> → <strong>{{ $pProximo->descricao ?? '' }}</strong>.
            As colunas mostram, dos alunos <strong>ativos no anterior</strong>, em que <strong>status</strong> estão no próximo período (um por aluno).
        </div>

        @include('rematricula._tabela', [
            'titulo' => 'Por Curso', 'rows' => $porCurso, 'totais' => $totCurso,
            'labelCol' => 'Curso', 'mostrarExtra' => false, 'statusCols' => $statusCols,
            'statusCor' => $statusCor, 'corHex' => $corHex,
            'labelAnterior' => $pAnterior->descricao ?? 'Anterior',
            'labelProximo' => $pProximo->descricao ?? 'Próximo',
        ])

        @include('rematricula._tabela', [
            'titulo' => 'Por Turma', 'rows' => $porTurma, 'totais' => $totTurma,
            'labelCol' => 'Turma', 'mostrarExtra' => true, 'statusCols' => $statusCols,
            'statusCor' => $statusCor, 'corHex' => $corHex,
            'labelAnterior' => $pAnterior->descricao ?? 'Anterior',
            'labelProximo' => $pProximo->descricao ?? 'Próximo',
        ])

        <div class="alert" style="background:#fdf6e3;border:1px solid #f0e2b8;color:#8a6d1a;margin-top:18px;">
            <strong>Inadimplência:</strong> considera-se <strong>inadimplente</strong> o aluno com título <em>em aberto e vencido do período anterior ({{ $pAnterior->descricao ?? '' }}) ou de períodos anteriores a ele</em>. Dívidas de períodos posteriores não contam aqui. O valor em aberto é a soma desses títulos vencidos acumulados até o período anterior.
        </div>
    @endif
@endsection
