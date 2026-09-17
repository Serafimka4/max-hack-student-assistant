@php
    $tabs = [
        ['route' => 'miniapp.home', 'match' => 'miniapp.home', 'label' => 'Главная', 'icon' => 'home'],
        ['route' => 'miniapp.schedule', 'match' => 'miniapp.schedule', 'label' => 'Расписание', 'icon' => 'calendar'],
        ['route' => 'miniapp.career', 'match' => ['miniapp.career', 'miniapp.offers.*', 'miniapp.assessments.*', 'miniapp.attempts.*'], 'label' => 'Карьера', 'icon' => 'briefcase'],
        ['route' => 'miniapp.help', 'match' => 'miniapp.help', 'label' => 'Помощь', 'icon' => 'help'],
    ];
@endphp
<nav aria-label="Разделы"
    class="fixed bottom-0 left-1/2 z-20 grid h-[68px] w-full max-w-[440px] -translate-x-1/2 grid-cols-4 border-t border-ink-line bg-ink px-2 pb-[env(safe-area-inset-bottom)]">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs(...(array) $tab['match']); @endphp
        <a href="{{ route($tab['route']) }}" wire:navigate @if ($active) aria-current="page" @endif
            @class(['flex flex-col items-center justify-center gap-1 text-[11px] font-semibold', 'text-white' => $active, 'text-muted-dark' => ! $active])>
            <span @class(['grid h-7 w-11 place-items-center rounded-full transition', 'bg-lime text-ink' => $active])>
                <x-miniapp.icon :name="$tab['icon']" :size="19" />
            </span>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
