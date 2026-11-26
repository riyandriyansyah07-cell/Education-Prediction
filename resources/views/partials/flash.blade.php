@if (session('status'))
    <div role="status" style="margin-bottom:1.5rem;padding:0.95rem 1.2rem;border-radius:12px;border:1px solid color-mix(in srgb, var(--ok) 32%, var(--line));border-left:4px solid var(--ok);background:var(--surface);display:flex;align-items:flex-start;gap:0.85rem;box-shadow:var(--shadow-card);">
        <span style="width:28px;height:28px;border-radius:8px;background:color-mix(in srgb, var(--ok) 14%, transparent);color:var(--ok);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <x-icon name="check-circle" size="size-4" />
        </span>
        <p class="text-sm text-ink" style="margin:0;line-height:1.55;">{{ session('status') }}</p>
    </div>
@endif
@if ($errors->has('activate') || $errors->has('is_active'))
    <div role="alert" style="margin-bottom:1.5rem;padding:0.95rem 1.2rem;border-radius:12px;border:1px solid color-mix(in srgb, var(--bad) 32%, var(--line));border-left:4px solid var(--bad);background:var(--surface);display:flex;align-items:flex-start;gap:0.85rem;box-shadow:var(--shadow-card);">
        <span style="width:28px;height:28px;border-radius:8px;background:color-mix(in srgb, var(--bad) 14%, transparent);color:var(--bad);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <x-icon name="warning-circle" size="size-4" />
        </span>
        <p class="text-sm text-ink" style="margin:0;line-height:1.55;">{{ $errors->first('activate') ?: $errors->first('is_active') }}</p>
    </div>
@endif
