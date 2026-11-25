@extends('layouts.app')

@section('title', 'Model prediksi')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise">Model prediksi</h1>
        <p class="rise mt-1.5 text-sm text-ink-2" style="--i: 1">Latih, evaluasi, dan aktifkan versi model ML (Random Forest / XGBoost + SHAP).</p>
    </div>
    <div class="flex flex-wrap gap-2 text-xs text-ink-2">
        <span style="padding:0.32rem 0.72rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">Target F1 &ge; <strong class="mono text-ink">{{ \App\Support\Fmt::num($targetF1) }}</strong></span>
        <span style="padding:0.32rem 0.72rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">Recall Tinggi &ge; <strong class="mono text-ink">{{ \App\Support\Fmt::num($targetRecall) }}</strong></span>
    </div>
</div>

<section class="mt-6 bg-surface" style="padding:1.35rem 1.5rem;" aria-labelledby="latih">
    <h2 id="latih" class="text-base font-semibold">Latih versi baru</h2>
    <form method="POST" action="{{ route('admin.model.latih') }}" class="mt-3 flex flex-wrap items-end gap-3">
        @csrf
        <div style="flex:1;min-width:220px;">
            <label for="dataset_id" class="label">Dataset historis</label>
            <select id="dataset_id" name="dataset_id" class="input" required>
                <option value="">Pilih dataset</option>
                @foreach ($datasets as $d)
                    <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->row_count }} baris)</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><x-icon name="brain" /> Latih &amp; uji</button>
    </form>
    @error('dataset_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    @if ($datasets->isEmpty())
        <p class="mt-3 text-xs text-ink-2">Belum ada dataset. <a href="{{ route('admin.historis') }}" class="font-medium text-accent hover:underline">Unggah data historis &rarr;</a></p>
    @endif
</section>

<section class="mt-8" aria-labelledby="versi">
    <h2 id="versi" class="text-base font-semibold">Versi model tersimpan</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="tbl min-w-[56rem]">
            <thead><tr><th>Versi</th><th>Algoritma</th><th>Ambang (S / T)</th><th>Macro-F1</th><th>Recall T</th><th>Latih / Uji</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
                @foreach ($versions as $v)
                    <tr>
                        <td><span class="mono font-semibold text-ink">{{ $v->version }}</span><span class="block text-xs text-ink-3">{{ $v->trained_at?->translatedFormat('j M Y') }}</span></td>
                        <td class="text-sm text-ink-2">{{ $v->algorithm }}</td>
                        <td class="num mono">{{ \App\Support\Fmt::num($v->params['sedang'] ?? 0) }} / {{ \App\Support\Fmt::num($v->params['tinggi'] ?? 0) }}</td>
                        <td class="num mono">{{ $v->macro_f1 !== null ? \App\Support\Fmt::num($v->macro_f1, 3) : '-' }}</td>
                        <td class="num mono">{{ $v->recall_high !== null ? \App\Support\Fmt::num($v->recall_high, 3) : '-' }}</td>
                        <td class="num">{{ $v->train_rows }} / {{ $v->test_rows }}</td>
                        <td>
                            @if ($v->is_active) <span class="risk risk-chip risk-rendah"><x-icon name="check-circle" /> Aktif</span>
                            @elseif ($v->meets_target) <span class="text-xs font-medium text-ink-2">Siap aktif</span>
                            @else <span class="text-xs text-ink-3">Di bawah target</span> @endif
                        </td>
                        <td class="text-right">
                            @if (! $v->is_active && $v->meets_target)
                                <form method="POST" action="{{ route('admin.model.aktifkan', $v) }}">@csrf<button class="btn btn-quiet btn-sm" type="submit">Aktifkan</button></form>
                            @endif
                        </td>
                    </tr>
                    @if (! empty($v->metrics['confusion']))
                        <tr>
                            <td colspan="8" style="padding-top:0;">
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-accent hover:underline">Matriks kebingungan{{ isset($v->metrics['baseline']) ? ' &amp; pembanding aturan' : '' }}</summary>
                                    @if (isset($v->metrics['baseline']))
                                        <p class="mt-2 text-ink-2">Aturan (data uji sama): F1 <span class="mono">{{ \App\Support\Fmt::num($v->metrics['baseline']['macro_f1'], 3) }}</span> · Recall T <span class="mono">{{ \App\Support\Fmt::num($v->metrics['baseline']['recall_high'], 3) }}</span></p>
                                    @endif
                                    @if (! empty($v->metrics['candidates']))
                                        <p class="mt-1 text-ink-2">Kandidat:
                                            @foreach ($v->metrics['candidates'] as $name => $c)
                                                {{ $name === 'xgboost' ? 'XGBoost' : 'Random Forest' }} (F1 {{ \App\Support\Fmt::num($c['test_macro_f1'], 3) }}, Rec {{ \App\Support\Fmt::num($c['test_recall_high'], 3) }})@if (! $loop->last) · @endif
                                            @endforeach
                                        </p>
                                    @endif
                                    <table class="tbl mt-3 max-w-md">
                                        <thead><tr><th>Aktual \ Prediksi</th><th>Rendah</th><th>Sedang</th><th>Tinggi</th></tr></thead>
                                        <tbody>
                                            @foreach (['rendah', 'sedang', 'tinggi'] as $a)
                                                <tr><td class="capitalize">{{ $a }}</td>@foreach (['rendah', 'sedang', 'tinggi'] as $p)<td class="num">{{ $v->metrics['confusion'][$a][$p] ?? 0 }}</td>@endforeach</tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </details>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs text-ink-3">Mode ML memakai <span class="mono">RISK_ENGINE=http</span> (<span class="mono">ml-service</span>); otomatis fallback ke model aturan bila layanan mati.</p>
</section>
@endsection