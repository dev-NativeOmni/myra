@props([
    'href',
    'active' => false,
    'icon',
])

<a href="{{ $href }}"
   @if($active) aria-current="page" @endif
   {{ $attributes->class([
       'flex items-center gap-3 px-3 py-2 rounded-lg transition',
       'bg-emerald-600 text-white shadow-xs' => $active,
       'text-slate-400 hover:text-white hover:bg-slate-800' => ! $active,
   ]) }}>
    <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
    </svg>
    <span>{{ $slot }}</span>
</a>
