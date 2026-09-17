@props(['result' => null, 'title', 'dark' => false])
@use('App\Enums\SkillOutcome')
@php
    $outcome = $result?->outcome;
    $label = match (true) {
        $result === null || $outcome === SkillOutcome::InsufficientData => 'Не оценено',
        $outcome === SkillOutcome::BelowBasic => 'Базовый не подтверждён',
        default => $result->level->getLabel(),
    };
@endphp
<div {{ $attributes }}>
    <div class="flex items-center justify-between gap-2 text-[13px]">
        <span class="font-bold">{{ $title }}</span>
        <span @class([
            'font-bold' => $outcome === SkillOutcome::Achieved,
            $dark ? 'text-lime' : 'text-ink' => $outcome === SkillOutcome::Achieved,
            $dark ? 'text-muted-dark' : 'text-muted' => $outcome !== SkillOutcome::Achieved,
        ])>
            {{ $label }}@if ($result && $outcome !== SkillOutcome::InsufficientData)<span class="font-medium {{ $dark ? 'text-muted-dark' : 'text-muted' }}"> · {{ $result->percent() }}%@if ($result->practicalPercent() !== null) · практика {{ $result->practicalPercent() }}%@endif</span>@endif
        </span>
    </div>
    @if ($result && $outcome !== SkillOutcome::InsufficientData)
        <div @class(['mt-2 h-2 overflow-hidden rounded-full', 'bg-ink-3' => $dark, 'bg-line' => ! $dark])>
            <i @class(['block h-full rounded-full', 'bg-lime' => $outcome === SkillOutcome::Achieved, 'bg-coral' => $outcome === SkillOutcome::BelowBasic])
                style="width: {{ max(4, $result->percent()) }}%"></i>
        </div>
    @else
        <div @class(['meter meter-hatched mt-2', 'opacity-40' => ! $dark])></div>
    @endif
</div>
