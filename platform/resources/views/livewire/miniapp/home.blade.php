<div>
    <header class="bg-ink pb-6 text-white">
        <div class="flex items-center justify-between px-4 py-3.5">
            <x-miniapp.logo />
            <x-miniapp.demo-badge />
        </div>

        <div class="px-4 pt-3.5">
            <div class="mb-3.5 flex flex-wrap gap-2">
                <span class="badge border border-lime text-lime">
                    {{ $student->organization->short_name ?? $student->organization->name }}@if ($student->group) · {{ $student->group->name }}@endif
                </span>
            </div>
            <h1 class="heading text-[34px]">Привет,<br>{{ str($student->user->name)->before(' ') }}</h1>
            <p class="mt-2.5 text-muted-dark">{{ \Illuminate\Support\Str::ucfirst($now->translatedFormat('l')) }}, {{ $now->translatedFormat('j F') }} · {{ mb_strtolower($parity->getLabel()) }}</p>
        </div>

        {{-- Текущая или ближайшая пара --}}
        <a href="{{ route('miniapp.schedule') }}" wire:navigate class="card-dark pressable relative mx-4 mt-[22px] w-auto overflow-hidden p-4">
            <svg class="pointer-events-none absolute -right-10 -top-2.5 size-[230px]" viewBox="0 0 200 200" aria-hidden="true">
                <circle cx="120" cy="80" r="78" fill="var(--color-blue)" />
                @for ($i = 0; $i < 9; $i++)
                    <line x1="0" y1="{{ 20 + $i * 16 }}" x2="200" y2="{{ 80 + $i * 16 }}" stroke="var(--color-lime)" stroke-opacity=".45" />
                @endfor
            </svg>

            <div class="relative">
                @if (! $student->group)
                    <span class="badge bg-lime text-ink">Расписание</span>
                    <div class="mt-16 text-lg font-extrabold">Выберите учебную группу</div>
                    <p class="mt-1 text-sm text-muted-dark">Тогда здесь появится ближайшая пара.</p>
                @elseif (! $current)
                    <span class="badge bg-lime text-ink">Сегодня</span>
                    <div class="mt-16 text-lg font-extrabold">Пар больше нет</div>
                    <p class="mt-1 text-sm text-muted-dark">Посмотрите расписание на неделю.</p>
                @else
                    @php $lesson = $current['lesson']; @endphp
                    <div class="flex items-center justify-between">
                        <span class="badge bg-lime text-ink">
                            <span class="dot"></span>{{ $current['is_now'] ? 'Сейчас · до '.$lesson->endTime() : 'Далее · в '.$lesson->startTime() }}
                        </span>
                        <x-miniapp.icon name="arrow" />
                    </div>
                    <div class="mt-8 flex items-end gap-3">
                        <span class="heading text-[76px] leading-[.8]">{{ str_pad($lesson->number, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="eyebrow pb-1 text-lime">пара</span>
                    </div>
                    <div class="mt-3.5 text-lg font-extrabold">{{ $lesson->title }}</div>
                    <div class="mt-1 flex flex-wrap gap-x-3 text-[13px] text-muted-dark">
                        <span class="inline-flex items-center gap-1"><x-miniapp.icon :name="$lesson->is_online ? 'video' : 'pin'" :size="14" />{{ $lesson->room }}</span>
                        <span>{{ $lesson->kind->getLabel() }}</span>
                        <span>{{ $lesson->teacher }}</span>
                    </div>
                    @if ($current['next'])
                        <div class="mt-3.5 border-t border-ink-line pt-3 text-[13px] text-muted-dark">
                            Далее в {{ $current['next']->startTime() }} — <span class="font-semibold text-white">{{ $current['next']->title }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </a>
    </header>

    @if ($deadline)
        <x-miniapp.section>
            <div class="card flex items-start gap-3 border-ink">
                <span class="icon-btn flex-none bg-lime"><x-miniapp.icon name="alert" :size="18" /></span>
                <div>
                    <div class="font-extrabold">Приём заявок: {{ $deadline->company_name }}</div>
                    <p class="mt-0.5 text-[13px] text-muted">
                        «{{ $deadline->title }}» — до {{ $deadline->apply_until->translatedFormat('j F') }}. Без места к контрольной дате координатор предложит варианты.
                    </p>
                </div>
            </div>
        </x-miniapp.section>
    @endif

    <x-miniapp.section title="Моя практика">
        <x-slot:action>
            <a href="{{ route('miniapp.career', ['view' => 'applications']) }}" wire:navigate class="link">Все заявки <x-miniapp.icon name="right" :size="14" /></a>
        </x-slot:action>

        @if ($application)
            <a href="{{ route('miniapp.offers.show', $application->offer) }}" wire:navigate class="card pressable">
                <div class="flex items-center justify-between gap-2">
                    <span class="eyebrow text-muted">{{ $application->offer->company_name }}</span>
                    <x-miniapp.application-badge :status="$application->status" />
                </div>
                <div class="heading mt-2 text-base">{{ $application->offer->title }}</div>
                <div class="mt-0.5 text-[13px] text-muted">Обновлено {{ $application->updated_at->translatedFormat('j M') }}</div>
                <x-miniapp.status-steps :status="$application->status" class="mt-3.5" />
            </a>
        @else
            <div class="card">
                <p class="text-[13px] text-muted">Активных заявок нет.</p>
                <a href="{{ route('miniapp.career') }}" wire:navigate class="btn-ink mt-3">Выбрать практику</a>
            </div>
        @endif
    </x-miniapp.section>

    <x-miniapp.section title="Быстро">
        @php
            $quick = [
                ['title' => 'Мои навыки', 'hint' => 'Диагностика', 'icon' => 'target', 'class' => 'bg-lime', 'url' => route('miniapp.career', ['view' => 'skills'])],
                ['title' => 'Практики', 'hint' => trans_choice(':count предложение|:count предложения|:count предложений', $offersCount), 'icon' => 'briefcase', 'class' => 'bg-blue', 'url' => route('miniapp.career')],
                ['title' => 'Шаблоны', 'hint' => 'Заявления и документы', 'icon' => 'file', 'class' => 'bg-coral', 'url' => route('miniapp.help').'#templates'],
                ['title' => 'Задать вопрос', 'hint' => 'FAQ и обращения', 'icon' => 'chat', 'class' => 'bg-white border border-line', 'url' => route('miniapp.help')],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-2.5">
            @foreach ($quick as $i => $item)
                <a href="{{ $item['url'] }}" wire:navigate class="pressable flex min-h-[132px] flex-col justify-between rounded-card p-3.5 text-ink {{ $item['class'] }}">
                    <div class="flex items-center justify-between">
                        <span class="eyebrow">0{{ $i + 1 }}</span>
                        <x-miniapp.icon :name="$item['icon']" />
                    </div>
                    <div>
                        <div class="heading text-sm">{{ $item['title'] }}</div>
                        <div class="mt-1 text-2xs opacity-75">{{ $item['hint'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-miniapp.section>

    @if ($assessment)
        <x-miniapp.section>
            <a href="{{ route('miniapp.career', ['view' => 'skills']) }}" wire:navigate class="card-dark pressable p-4">
                <div class="flex items-center justify-between">
                    <span class="eyebrow text-lime">{{ $assessment->direction }}</span>
                    <x-miniapp.icon name="arrow" :size="18" />
                </div>
                <div class="mt-3 font-extrabold">{{ $assessment->title }}</div>
                <p class="mt-1 text-[13px] text-muted-dark">Навыки пока не оценены. Результат диагностики можно приложить к заявке.</p>
            </a>
        </x-miniapp.section>
    @endif
</div>
