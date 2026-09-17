@props(['title', 'eyebrow' => null])
<header class="bg-ink pb-[22px] text-white">
    <div class="flex items-center justify-between px-4 py-3.5">
        <x-miniapp.logo />
        <x-miniapp.demo-badge />
    </div>
    <div class="px-4 pt-2.5">
        @if ($eyebrow)
            <div class="mb-3"><span class="badge border border-lime text-lime">{{ $eyebrow }}</span></div>
        @endif
        <div class="flex items-end justify-between gap-3">
            <h1 class="heading text-[30px]">{{ $title }}</h1>
            {{ $right ?? '' }}
        </div>
    </div>
    {{ $slot }}
</header>
