{{-- Ghaznowyan Hyper logo drawn in code: sharp at any size, text editable here.
     tone="light" for dark backgrounds (sidebar), tone="dark" for light ones (login). --}}
@props(['tone' => 'dark'])

@once
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@800&family=Sora:wght@700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <style>
        .mk-logo { display: inline-flex; align-items: center; gap: .55em; font-size: 16px; line-height: 1; text-decoration: none; }
        .mk-logo--light { color: #fff; }
        .mk-logo--dark { color: #0f3d2e; }
        .mk-logo-cart { width: 3em; height: auto; flex-shrink: 0; }
        .mk-logo-text { display: flex; flex-direction: column; align-items: center; gap: .18em; }
        .mk-logo-fa { font-family: "Vazirmatn", Tahoma, sans-serif; font-weight: 800; font-size: 1.45em; line-height: 1.15; direction: rtl; }
        .mk-logo-en { font-family: "Sora", Arial, sans-serif; font-weight: 700; font-size: .92em; letter-spacing: .01em; white-space: nowrap; }
        .mk-logo-slogan { font-family: "Dancing Script", "Segoe Script", cursive; font-weight: 700; font-size: 1em; color: #d4a017; }
    </style>
@endonce

<span {{ $attributes->merge(['class' => 'mk-logo mk-logo--'.$tone]) }} role="img" aria-label="Ghaznowyan Hyper">
    <svg class="mk-logo-cart" viewBox="0 0 64 56" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2 5h8l4 7"/>
        <path d="M14 12h46l-7 24H21z"/>
        <path d="M17 21h40M19 29h36M27 12l2 24M39 12v24M51 12l-3 24" stroke-width="3"/>
        <path d="M21 36l-3 6h36"/>
        <circle cx="24" cy="49" r="4.5" fill="currentColor" stroke="none"/>
        <circle cx="48" cy="49" r="4.5" fill="currentColor" stroke="none"/>
    </svg>
    <span class="mk-logo-text" aria-hidden="true">
        <span class="mk-logo-fa" lang="fa">غزنویان هایپر</span>
        <span class="mk-logo-en">Ghaznowyan Hyper</span>
        <span class="mk-logo-slogan">Better Life</span>
    </span>
</span>
