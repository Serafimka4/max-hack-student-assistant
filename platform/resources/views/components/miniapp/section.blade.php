@props(['title' => null])
<section {{ $attributes->merge(['class' => 'px-4 pt-6']) }}>
    @if ($title || isset($action))
        <div class="mb-3 flex items-end justify-between gap-3">
            <h2 class="heading text-[22px]">{{ $title }}</h2>
            {{ $action ?? '' }}
        </div>
    @endif
    {{ $slot }}
</section>
