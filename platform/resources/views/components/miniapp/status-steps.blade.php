@props(['status'])
@php
    $steps = ['Подана', 'Рассмотрение', $status === \App\Enums\ApplicationStatus::Rejected ? 'Отказ' : 'Принят'];
    $reached = match ($status) {
        \App\Enums\ApplicationStatus::InReview => 1,
        \App\Enums\ApplicationStatus::Accepted, \App\Enums\ApplicationStatus::Rejected => 2,
        default => 0,
    };
@endphp
<div {{ $attributes }}>
    <div class="grid grid-cols-3 gap-1">
        @foreach ($steps as $i => $step)
            <div @class(['h-1.5 rounded-md', 'bg-ink' => $i <= $reached, 'bg-line' => $i > $reached])></div>
        @endforeach
    </div>
    <div class="mt-1.5 grid grid-cols-3 gap-1 text-2xs">
        @foreach ($steps as $i => $step)
            <span @class([
                'font-bold' => $i === $reached,
                'opacity-55' => $i > $reached,
                'text-center' => $i === 1,
                'text-right' => $i === 2,
            ])>{{ $step }}</span>
        @endforeach
    </div>
</div>
