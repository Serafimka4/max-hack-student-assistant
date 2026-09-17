<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Цифровой помощник' }}</title>
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="relative mx-auto min-h-dvh max-w-[440px] overflow-x-hidden bg-paper pb-[92px]">
        {{ $slot }}
    </div>

    <x-miniapp.tabbar />
    <livewire:mini-app.report-sheet />

    {{-- Всплывающее сообщение: $this->dispatch('toast', message: '…', tone: 'success'|'error') --}}
    <div x-data="{ message: null, tone: 'success', timer: null }"
        x-on:toast.window="message = $event.detail.message; tone = $event.detail.tone ?? 'success'; clearTimeout(timer); timer = setTimeout(() => message = null, 2800)"
        x-show="message" x-cloak role="status" x-transition.opacity
        :class="tone === 'error' ? 'bg-danger text-white' : 'bg-lime text-ink'"
        class="fixed bottom-[82px] left-1/2 z-50 flex w-[calc(100%-32px)] max-w-[408px] -translate-x-1/2 animate-rise items-center gap-2.5 rounded-2xl px-3.5 py-3 text-sm font-bold">
        <x-miniapp.icon name="check" :size="18" :stroke="2.6" x-show="tone !== 'error'" />
        <x-miniapp.icon name="alert" :size="18" x-show="tone === 'error'" />
        <span x-text="message"></span>
    </div>
</body>
</html>
