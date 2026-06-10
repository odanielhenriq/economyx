@props([
    'variant' => 'default', // default | warning | success
])

@php
    $classes = match ($variant) {
        'warning' => 'bg-amber-50 border-amber-200 text-amber-900',
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-900',
        default   => 'bg-slate-50 border-slate-200 text-slate-600',
    };
@endphp

<div {{ $attributes->merge(['class' => "px-4 py-3 text-sm border rounded-xl leading-relaxed {$classes}"]) }} role="note">
    {{ $slot }}
</div>
