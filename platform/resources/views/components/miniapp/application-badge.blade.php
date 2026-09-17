@props(['status'])
@php
    $tone = match ($status) {
        \App\Enums\ApplicationStatus::InReview => 'bg-blue/15 text-[#3159cf]',
        \App\Enums\ApplicationStatus::Accepted => 'bg-lime text-ink',
        \App\Enums\ApplicationStatus::Rejected => 'bg-coral/15 text-[#b8492a]',
        default => 'bg-ink/[.07] text-ink',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge {$tone}"]) }}><span class="dot"></span>{{ $status->getLabel() }}</span>
