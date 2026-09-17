@if (config('miniapp.demo_data'))
    <span {{ $attributes->merge(['class' => 'badge bg-ink-3 text-muted-dark']) }}>Демо-данные</span>
@endif
