<div>
    <x-miniapp.header :eyebrow="$parity->getLabel().' · '.$days->first()['date']->translatedFormat('j').'–'.$days->last()['date']->translatedFormat('j F')">
        <x-slot:title>Распи&shy;сание</x-slot:title>
        <x-slot:right>
            <div x-data="{ open: false }" class="relative flex-none">
                <button type="button" x-on:click="open = !open" class="btn-ghost-dark min-h-9 whitespace-nowrap px-3 text-[13px]" :aria-expanded="open">
                    {{ $group?->name ?? 'Выбрать группу' }}<x-miniapp.icon name="down" :size="16" />
                </button>
                <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.opacity
                    class="absolute right-0 z-30 mt-2 max-h-72 w-52 overflow-y-auto rounded-2xl border border-ink-line bg-ink-2 p-1.5">
                    @forelse ($groups as $option)
                        <button type="button" wire:click="chooseGroup({{ $option->id }})" x-on:click="open = false"
                            @class(['block w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold', 'bg-lime text-ink' => $option->id === $group?->id, 'hover:bg-ink-3' => $option->id !== $group?->id])>
                            {{ $option->name }}
                        </button>
                    @empty
                        <p class="px-3 py-2 text-sm text-muted-dark">Группы ещё не загружены.</p>
                    @endforelse
                </div>
            </div>
        </x-slot:right>

        <div class="mt-5 flex items-center gap-1.5 px-4">
            <button type="button" wire:click="shiftWeek(-1)" class="icon-btn size-8 flex-none bg-ink-3 text-white" aria-label="Предыдущая неделя">
                <x-miniapp.icon name="left" :size="16" />
            </button>
            <div class="grid flex-1 grid-cols-6 gap-1.5" role="tablist" aria-label="Дни недели">
                @foreach ($days as $day)
                    @php $isSelected = $day['date']->isSameDay($selected); $isToday = $day['date']->isSameDay($now); @endphp
                    <button type="button" role="tab" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        wire:click="selectDate('{{ $day['date']->toDateString() }}')"
                        @class(['relative flex flex-col items-center gap-0.5 rounded-[14px] border pb-3 pt-2.5',
                            'border-lime bg-lime text-ink' => $isSelected,
                            'border-ink-line bg-ink-2 text-muted-dark' => ! $isSelected])>
                        <span class="text-2xs">{{ mb_convert_case($day['date']->minDayName, MB_CASE_TITLE) }}</span>
                        <span @class(['heading text-[17px]', 'text-white' => ! $isSelected])>{{ $day['date']->day }}</span>
                        <span class="mt-1 flex h-1 gap-[3px]">
                            @for ($i = 0; $i < $day['count']; $i++)<i class="size-1 rounded-full bg-current opacity-70"></i>@endfor
                        </span>
                        @if ($isToday)
                            <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 rounded-md bg-blue px-1.5 py-px text-[8.5px] font-bold uppercase tracking-wide text-white">сегодня</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="shiftWeek(1)" class="icon-btn size-8 flex-none bg-ink-3 text-white" aria-label="Следующая неделя">
                <x-miniapp.icon name="right" :size="16" />
            </button>
        </div>
    </x-miniapp.header>

    <x-miniapp.section>
        @if (! $group)
            <div class="card p-8 text-center">
                <div class="heading text-[22px]">Выберите группу</div>
                <p class="mt-1.5 text-[13px] text-muted">Нажмите кнопку в шапке — выбор сохранится.</p>
            </div>
        @elseif ($lessons->isEmpty())
            <div class="card p-8 text-center">
                <div class="heading text-[22px]">Пар нет</div>
                <p class="mt-1.5 text-[13px] text-muted">Свободный день по расписанию группы.</p>
            </div>
        @else
            <div class="flex flex-col gap-2.5" wire:loading.class="opacity-60">
                @foreach ($lessons as $lesson)
                    @php
                        $isNow = $lesson->id === $nowLessonId;
                        $kindTone = match ($lesson->kind) {
                            \App\Enums\LessonKind::Practice => 'bg-blue/15 text-[#3159cf]',
                            \App\Enums\LessonKind::Lab => 'bg-coral/15 text-[#b8492a]',
                            default => 'bg-ink/[.07] text-ink',
                        };
                    @endphp
                    <div class="grid grid-cols-[52px_1fr] gap-2.5" wire:key="lesson-{{ $lesson->id }}">
                        <div class="flex flex-col pt-3.5">
                            <span class="heading text-[15px]">{{ $lesson->startTime() }}</span>
                            <span class="text-2xs text-muted">{{ $lesson->endTime() }}</span>
                        </div>
                        <div @class(['card p-3.5', 'border-lime bg-lime' => $isNow])>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span @class(['eyebrow', 'text-ink/65' => $isNow, 'text-muted' => ! $isNow])>{{ $lesson->number }} пара</span>
                                    @if ($isNow)
                                        <span class="badge bg-ink text-white"><span class="dot text-lime"></span>Идёт</span>
                                    @endif
                                </div>
                                <span class="badge {{ $kindTone }}">{{ $lesson->kind->getLabel() }}</span>
                            </div>
                            <div class="mt-2 text-base font-extrabold">{{ $lesson->title }}</div>
                            <div @class(['mt-1 flex flex-wrap gap-x-3 text-[13px]', 'text-ink/65' => $isNow, 'text-muted' => ! $isNow])>
                                <span class="inline-flex items-center gap-1"><x-miniapp.icon :name="$lesson->is_online ? 'video' : 'pin'" :size="14" />{{ $lesson->room }}</span>
                                <span>{{ $lesson->teacher }}</span>
                            </div>
                            <button type="button" @class(['link mt-2.5 font-semibold', 'text-ink/65' => $isNow, 'text-muted' => ! $isNow])
                                x-on:click="$dispatch('open-report', { category: 'schedule', subjectType: 'lesson', subjectId: {{ $lesson->id }} })">
                                <x-miniapp.icon name="alert" :size="14" />Сообщить об ошибке
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($group)
            <div class="mt-[18px] flex items-start gap-2 text-[13px] text-muted">
                <x-miniapp.icon name="clock" :size="15" class="mt-0.5" />
                <span>
                    Источник: {{ $group->schedule_source ?? 'не указан' }}.
                    @if ($group->schedule_updated_at)
                        Обновлено {{ $group->schedule_updated_at->timezone(config('miniapp.timezone'))->translatedFormat('j M, H:i') }}.
                    @else
                        Время обновления неизвестно — сверяйтесь с официальным расписанием.
                    @endif
                </span>
            </div>
        @endif
    </x-miniapp.section>
</div>
