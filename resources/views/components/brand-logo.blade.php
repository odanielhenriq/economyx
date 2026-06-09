@props([
    'size' => 'md',
    'showName' => true,
    'variant' => 'dark',
])

@php
    $sizes = [
        'sm' => ['badge' => 'w-9 h-9 rounded-xl text-sm', 'name' => 'text-lg'],
        'md' => ['badge' => 'w-12 h-12 rounded-2xl text-lg', 'name' => 'text-xl'],
        'lg' => ['badge' => 'w-16 h-16 rounded-2xl text-2xl', 'name' => 'text-3xl'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
    $nameClass = $variant === 'dark' ? 'text-white' : 'text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <div class="{{ $s['badge'] }} bg-green-500 flex items-center justify-center flex-shrink-0">
        <span class="font-bold text-white tracking-tight leading-none">E$</span>
    </div>
    @if ($showName)
        <span class="{{ $s['name'] }} font-bold {{ $nameClass }} tracking-tight">Economyx</span>
    @endif
</div>
