@extends('layouts.app')
@section('titulo', 'Dashboard')

@section('conteudo')
    <div class="grid cols-4" style="margin-bottom:22px;">
        <div class="card stat">
            <div class="ico">▤</div>
            <div class="label">Matrículas</div>
            <div class="value">{{ number_format($totalMatriculas, 0, ',', '.') }}</div>
        </div>
        <div class="card stat">
            <div class="ico">◷</div>
            <div class="label">Períodos Letivos</div>
            <div class="value">{{ $totalPeriodos }}</div>
        </div>
        <div class="card stat">
            <div class="ico">◔</div>
            <div class="label">Anos Distintos</div>
            <div class="value">{{ $totalAnos }}</div>
        </div>
        <div class="card stat">
            <div class="ico">⟳</div>
            <div class="label">Última Sincronização</div>
            <div class="value" style="font-size:16px;line-height:1.4;margin-top:10px;">
                @if ($ultimaSync)
                    @php $map = ['concluido'=>'ok','erro'=>'err','executando'=>'warn']; @endphp
                    <span class="badge {{ $map[$ultimaSync->status] ?? 'mut' }}">{{ ucfirst($ultimaSync->status) }}</span><br>
                    <small class="muted">{{ optional($ultimaSync->finalizado_em ?? $ultimaSync->iniciado_em)->format('d/m/Y H:i') }}</small>
                @else
                    <span class="muted" style="font-size:14px;">Nenhuma ainda</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <div class="card-h"><h2>Matrículas por Período Letivo</h2></div>
            <div class="card-b">
                @php $maxPer = $porPeriodo->max('total') ?: 1; @endphp
                @forelse ($porPeriodo as $linha)
                    <div style="margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                            <a href="{{ route('matriculas.index', ['periodo' => $linha->id_periodo_letivo]) }}">
                                <strong>{{ $linha->descricao }}</strong>
                                <span class="muted" style="font-weight:400;">· {{ \Illuminate\Support\Str::of($linha->org_descricao)->title() }}</span>
                            </a>
                            <span class="muted">{{ number_format($linha->total, 0, ',', '.') }}</span>
                        </div>
                        <div style="background:#eef0f3;border-radius:6px;height:9px;overflow:hidden;">
                            <div style="background:var(--teal);height:100%;width:{{ round($linha->total / $maxPer * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="empty"><div class="big">◔</div>Sem dados. Rode a sincronização.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Matrículas por Status</h2></div>
            <div class="card-b">
                @forelse ($porStatus as $s)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line);">
                        <span class="badge info">{{ $s->status ?: '—' }}</span>
                        <strong>{{ number_format($s->total, 0, ',', '.') }}</strong>
                    </div>
                @empty
                    <div class="empty"><div class="big">▤</div>Sem dados ainda.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div style="margin-top:22px;">
        <a href="{{ route('ingestao.index') }}" class="btn primary">⟳ Ir para Sincronização</a>
        <a href="{{ route('matriculas.index') }}" class="btn ghost">Ver Matrículas</a>
    </div>
@endsection
