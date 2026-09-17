@props(['status'])
@php
    $tone = match ($status) {
        \App\Enums\TicketStatus::New => 'bg-coral/15 text-[#b8492a]',
        \App\Enums\TicketStatus::InProgress => 'bg-blue/15 text-[#3159cf]',
        \App\Enums\TicketStatus::Resolved => 'bg-lime text-ink',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge {$tone}"]) }}><span class="dot"></span>{{ $status->getLabel() }}</span>
