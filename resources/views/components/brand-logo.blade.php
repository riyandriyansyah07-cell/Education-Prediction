@props(['size' => 38, 'class' => ''])
<svg viewBox="0 0 40 40" width="{{ $size }}" height="{{ $size }}" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" {{ $attributes->merge(['class' => 'shrink-0 ' . $class]) }}>
    <rect x="2" y="2" width="36" height="36" rx="11" fill="#0b1220"/>
    <path d="M11 26L17.5 19.5L22 23L29 14.5" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M24 14.5H29V19.5" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
