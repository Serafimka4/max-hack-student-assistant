<div>
    <x-miniapp.header title="Карьера" eyebrow="Практики и стажировки">
        <div class="mt-5 px-4">
            <div class="grid grid-cols-3 gap-1 rounded-[14px] border border-ink-line bg-ink-2 p-1" role="tablist">
                @foreach (['offers' => 'Практики', 'skills' => 'Мои навыки', 'applications' => 'Заявки'] as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}" wire:click="$set('view', '{{ $key }}')"
                        @class(['h-[38px] rounded-[10px] text-[13px] font-bold', 'bg-white text-ink' => $view === $key, 'text-muted-dark' => $view !== $key])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </x-miniapp.header>

    @if ($view === 'offers')
        <x-miniapp.section>
            <div class="no-scrollbar mb-3.5 flex gap-1.5 overflow-x-auto pb-0.5">
                <button type="button" wire:click="$set('direction', '')" @class(['chip', 'chip-active' => ! $activeDirection])>Все</button>
                @foreach ($directions as $direction)
                    <button type="button" wire:click="$set('direction', '{{ $direction->value }}')" @class(['chip', 'chip-active' => $activeDirection === $direction])>
                        {{ $direction->getLabel() }}
                    </button>
                @endforeach
            </div>

            <div class="flex flex-col gap-3" wire:loading.class="opacity-60">
                @forelse ($offers as $i => $row)
                    @php $offer = $row['offer']; $ok = $row['match']->where('state', 'ok')->count(); @endphp
                    <a href="{{ route('miniapp.offers.show', $offer) }}" wire:navigate wire:key="offer-{{ $offer->id }}"
                        class="pressable flex min-h-[210px] flex-col rounded-card p-4 text-ink bg-{{ $offer->direction->accent() }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow">0{{ $i + 1 }} · {{ $offer->company_name }}</span>
                            @if (in_array($offer->id, $appliedOfferIds, true))
                                <span class="badge bg-ink text-white">Заявка подана</span>
                            @else
                                <x-miniapp.icon name="arrow" />
                            @endif
                        </div>
                        <div class="heading mt-3.5 text-[21px]">{{ $offer->title }}</div>
                        <div class="mt-1.5 text-[13px] opacity-80">
                            {{ $offer->format }}@if ($offer->starts_on) · {{ $offer->starts_on->translatedFormat('j M') }} — {{ $offer->ends_on?->translatedFormat('j M') }}@endif · мест: {{ $offer->places }}
                        </div>
                        <div class="mt-[18px] text-2xs font-bold">{{ $offer->skills->pluck('title')->join(' · ') }}</div>
                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-ink/15 pt-3">
                            <span class="inline-flex items-center gap-1.5 text-2xs font-bold">
                                <x-miniapp.match-dots :match="$row['match']" />
                                @if ($ok === 0 && $row['match']->every(fn ($m) => $m['state'] === 'unknown'))
                                    Навыки не оценены
                                @else
                                    {{ $ok }} из {{ $row['match']->count() }} навыков подтверждено
                                @endif
                            </span>
                            @if ($offer->apply_until)
                                <span class="text-2xs font-bold">{{ $offer->isOpen() ? 'до '.$offer->apply_until->translatedFormat('j M') : 'приём закрыт' }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="card p-8 text-center">
                        <div class="heading text-[22px]">Пока пусто</div>
                        <p class="mt-1.5 text-[13px] text-muted">Предложений по этому направлению нет.</p>
                    </div>
                @endforelse
            </div>
        </x-miniapp.section>
    @endif

    @if ($view === 'skills')
        <x-miniapp.section>
            <div class="card-dark relative overflow-hidden p-[18px]">
                <div class="pointer-events-none absolute -right-16 -top-16 size-[190px] rounded-full bg-blue opacity-90"></div>
                <div class="relative">
                    <span class="eyebrow text-lime">Профиль навыков</span>
                    @php $graded = $assessments->first(fn ($row) => $row['last'] !== null); @endphp
                    <div class="mt-3.5 text-[13px] text-muted-dark">Предварительный грейд</div>
                    <div class="heading mt-1 text-[30px]">{{ $graded['grade'] ?? 'Не оценено' }}</div>
                    @if ($graded)
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="badge bg-ink-3 text-muted-dark">{{ $graded['assessment']->direction }} · v{{ $graded['last']->version->version }}</span>
                            @php $graded['last']->loadMissing('practical'); @endphp
                            <span @class(['badge', 'bg-lime text-ink' => $graded['last']->practical?->status === \App\Enums\PracticalStatus::Reviewed, 'bg-coral/20 text-coral' => $graded['last']->practical?->status !== \App\Enums\PracticalStatus::Reviewed])>
                                {{ $graded['last']->practical ? 'Практика: '.mb_strtolower($graded['last']->practical->status->getLabel()) : 'Практика не проверялась' }}
                            </span>
                        </div>
                    @endif
                </div>

                @if ($results->isNotEmpty())
                    <div class="relative mt-5 flex flex-col gap-3.5">
                        @foreach ($results as $result)
                            <x-miniapp.skill-meter :result="$result" :title="$result->skill->title" dark />
                        @endforeach
                    </div>
                @endif

                <p class="relative mt-4 text-2xs text-muted-dark">
                    «Не оценено» — нет проверенных данных, а не низкий результат. Прикладной уровень и грейд «Junior» — только после проверки практического задания специалистом.
                </p>
            </div>
        </x-miniapp.section>

        <x-miniapp.section title="Диагностика">
            <div class="flex flex-col gap-2.5">
                @forelse ($assessments as $row)
                    @php $assessment = $row['assessment']; $version = $assessment->publishedVersion; @endphp
                    <a href="{{ route('miniapp.assessments.show', $assessment) }}" wire:navigate class="card pressable" wire:key="assessment-{{ $assessment->id }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow text-muted">{{ $assessment->organization->short_name ?? $assessment->organization->name }}</span>
                            @if ($row['open'])
                                <span class="badge bg-blue/15 text-[#3159cf]"><span class="dot"></span>Не завершён</span>
                            @elseif ($row['last'])
                                <span class="badge bg-lime text-ink">Пройден</span>
                            @else
                                <span class="badge bg-ink/[.07] text-ink">v{{ $version->version }}</span>
                            @endif
                        </div>
                        <div class="mt-2 font-extrabold">{{ $assessment->title }}</div>
                        <div class="mt-0.5 text-[13px] text-muted">
                            {{ $assessment->direction }} · {{ trans_choice(':count вопрос|:count вопроса|:count вопросов', $version->questions->count()) }}@if ($assessment->duration_minutes) · {{ $assessment->duration_minutes }} мин@endif
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-line pt-3 text-[13px] font-bold">
                            <span>
                                @if ($row['open']) Продолжить
                                @elseif ($row['last']) Результат от {{ $row['last']->submitted_at->timezone(config('miniapp.timezone'))->translatedFormat('j M') }}
                                @else Начать диагностику
                                @endif
                            </span>
                            <x-miniapp.icon name="arrow" :size="18" />
                        </div>
                    </a>
                @empty
                    <div class="card text-[13px] text-muted">Опубликованных тестов пока нет.</div>
                @endforelse
            </div>
        </x-miniapp.section>
    @endif

    @if ($view === 'applications')
        <x-miniapp.section>
            <div class="flex flex-col gap-3">
                @forelse ($applications as $application)
                    <a href="{{ route('miniapp.offers.show', $application->offer) }}" wire:navigate wire:key="application-{{ $application->id }}" class="card pressable">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eyebrow text-muted">{{ $application->offer->company_name }}</span>
                            <x-miniapp.application-badge :status="$application->status" />
                        </div>
                        <div class="heading mt-2 text-base">{{ $application->offer->title }}</div>
                        @if ($application->status->isActive())
                            <x-miniapp.status-steps :status="$application->status" class="mt-3.5" />
                        @endif
                        <div class="my-3 h-px bg-line"></div>
                        <div class="flex flex-col gap-1.5">
                            @foreach ($application->history->reverse()->values() as $i => $change)
                                <div class="flex items-center justify-between text-[13px]">
                                    <span class="inline-flex items-center gap-2">
                                        <span @class(['size-2 rounded-full', 'bg-ink' => $i === 0, 'bg-line' => $i > 0])></span>
                                        {{ $change->status->getLabel() }}
                                    </span>
                                    <span class="text-muted">{{ $change->created_at->timezone(config('miniapp.timezone'))->translatedFormat('j M') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2.5 flex items-center gap-1.5 text-[13px] text-muted">
                            <x-miniapp.icon :name="$application->share_results ? 'check' : 'lock'" :size="14" />
                            {{ $application->share_results ? 'Результаты диагностики разрешено показать' : 'Без результатов диагностики' }}
                        </div>
                    </a>
                @empty
                    <div class="card">
                        <p class="text-muted">Заявок пока нет.</p>
                        <button type="button" wire:click="$set('view', 'offers')" class="btn-ink mt-3">Смотреть практики</button>
                    </div>
                @endforelse
            </div>
        </x-miniapp.section>
    @endif
</div>
