<div data-back-url="{{ route('miniapp.career', ['view' => 'skills']) }}">
    <header class="relative overflow-hidden bg-ink px-4 pb-6 pt-3.5 text-white">
        <div class="pointer-events-none absolute -right-16 -top-10 size-56 rounded-full bg-blue opacity-90"></div>
        <div class="relative flex items-center justify-between">
            <a href="{{ route('miniapp.career', ['view' => 'skills']) }}" wire:navigate class="icon-btn bg-ink-3" aria-label="К профилю навыков">
                <x-miniapp.icon name="left" />
            </a>
            <x-miniapp.demo-badge />
        </div>
        <div class="relative">
            <div class="eyebrow mt-[22px] text-lime">{{ $assessment->direction }} · результат</div>
            <div class="mt-3 text-[13px] text-muted-dark">Предварительный грейд</div>
            <div class="heading mt-1 text-[34px]">{{ $grade ?? 'Не определён' }}</div>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="badge bg-ink-3 text-muted-dark">Тест v{{ $attempt->version->version }} · {{ $attempt->submitted_at->timezone(config('miniapp.timezone'))->translatedFormat('j F') }}</span>
                @php $practical = $attempt->practical; @endphp
                @if (! $attempt->version->hasPractical())
                    <span class="badge bg-ink-3 text-muted-dark">Без практической части</span>
                @elseif (! $practical)
                    <span class="badge bg-coral/20 text-coral">Практика не отправлена</span>
                @else
                    <span @class(['badge', 'bg-lime text-ink' => $practical->status === \App\Enums\PracticalStatus::Reviewed, 'bg-coral/20 text-coral' => $practical->status !== \App\Enums\PracticalStatus::Reviewed])>
                        Практика: {{ mb_strtolower($practical->status->getLabel()) }}
                    </span>
                @endif
                @if ($attempt->expired)
                    <span class="badge bg-ink-3 text-muted-dark">Завершено по времени</span>
                @endif
            </div>
        </div>
    </header>

    <x-miniapp.section title="По навыкам">
        <div class="card flex flex-col gap-4">
            @foreach ($results as $result)
                <x-miniapp.skill-meter :result="$result" :title="$result->skill->title" />
            @endforeach
        </div>
        <p class="mt-2 text-2xs text-muted">
            Базовый уровень — от {{ $attempt->version->basic_threshold }}% при не менее {{ $attempt->version->min_questions_per_skill }} вопросах на навык.
            Прикладной (от {{ $attempt->version->applied_threshold }}% по практике) и уверенный (от {{ $attempt->version->confident_threshold }}%) уровни и грейд «Junior» присваиваются только после проверки практического задания специалистом.
            Прохождение без наблюдателя — результат предварительный.
        </p>
    </x-miniapp.section>

    @if ($attempt->version->hasPractical())
        @php
            $rubric = collect($attempt->version->practical_rubric);
            $skillTitles = $results->pluck('skill.title', 'skill_id');
            $review = $practical?->latestReview;
        @endphp
        <x-miniapp.section title="Практическое задание">
            <div class="card">
                <p class="whitespace-pre-line text-sm">{{ $attempt->version->practical_task }}</p>
                <div class="mt-3 rounded-2xl bg-paper p-3">
                    <div class="eyebrow text-muted">Критерии проверки</div>
                    <ul class="mt-2 flex flex-col gap-1.5 text-[13px]">
                        @foreach ($rubric as $i => $criterion)
                            <li class="flex items-start justify-between gap-3">
                                <span>{{ $criterion['criterion'] }} <span class="text-muted">· {{ $skillTitles[$criterion['skill_id']] ?? '' }}</span></span>
                                <span class="flex-none font-bold">
                                    @if ($review){{ $review->scores[$i] ?? 0 }} / @endif{{ $criterion['max_points'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if (! $practical || $practical->status === \App\Enums\PracticalStatus::Pending)
                    <form wire:submit="submitPractical" class="mt-4 flex flex-col gap-2.5">
                        <label class="text-[13px] font-bold" for="practical-link">Ссылка на решение</label>
                        <input id="practical-link" type="url" wire:model="link" placeholder="https://github.com/…" inputmode="url"
                            class="h-11 rounded-2xl border border-line bg-paper px-3.5 outline-none focus:border-ink">
                        @error('link')<p class="text-[13px] text-danger">{{ $message }}</p>@enderror
                        <label class="text-[13px] font-bold" for="practical-answer">Описание или код</label>
                        <textarea id="practical-answer" wire:model="answer" rows="4" placeholder="Что сделано и как проверить"
                            class="resize-y rounded-2xl border border-line bg-paper px-3.5 py-3 outline-none focus:border-ink"></textarea>
                        @error('answer')<p class="text-[13px] text-danger">{{ $message }}</p>@enderror
                        <button type="submit" wire:loading.attr="disabled" class="btn-ink w-full">
                            {{ $practical ? 'Обновить решение' : 'Отправить на проверку' }}
                        </button>
                        <p class="text-2xs text-muted">
                            @if ($practical)
                                Отправлено {{ $practical->submitted_at->timezone(config('miniapp.timezone'))->translatedFormat('j F, H:i') }} — ожидает проверки. До проверки решение можно заменить.
                            @else
                                Проверяет специалист организации по критериям выше. Решение оценивается без вашего имени.
                            @endif
                        </p>
                    </form>
                @else
                    @if ($review?->comment)
                        <div class="mt-3 rounded-2xl border border-line p-3 text-sm">
                            <div class="eyebrow text-muted">Комментарий проверяющего</div>
                            <p class="mt-1">{{ $review->comment }}</p>
                        </div>
                    @endif
                    <p class="mt-3 text-2xs text-muted">
                        {{ $practical->status === \App\Enums\PracticalStatus::Appeal ? 'Пересмотр запрошен — оценка будет проверена повторно.' : 'Проверено '.$practical->reviewed_at->timezone(config('miniapp.timezone'))->translatedFormat('j F').'.' }}
                    </p>
                    @if ($practical->status === \App\Enums\PracticalStatus::Reviewed && ! $practical->reviews->contains('is_appeal', true))
                        <details class="mt-3">
                            <summary class="link cursor-pointer text-muted">Не согласен с оценкой</summary>
                            <form wire:submit="requestAppeal" class="mt-2.5 flex flex-col gap-2">
                                <textarea wire:model="appealReason" rows="3" placeholder="Какой критерий оценён неверно и почему"
                                    class="resize-y rounded-2xl border border-line bg-paper px-3.5 py-3 outline-none focus:border-ink"></textarea>
                                @error('appealReason')<p class="text-[13px] text-danger">{{ $message }}</p>@enderror
                                <button type="submit" class="btn-ghost w-full">Запросить пересмотр</button>
                            </form>
                        </details>
                    @endif
                @endif
            </div>
        </x-miniapp.section>
    @endif

    @if ($toImprove->isNotEmpty())
        <x-miniapp.section title="Что подтянуть">
            <div class="flex flex-col gap-2.5">
                @foreach ($toImprove as $i => $result)
                    <div class="card flex gap-3">
                        <span class="w-1.5 flex-none self-stretch rounded-full {{ ['bg-lime', 'bg-blue', 'bg-coral'][$i % 3] }}"></span>
                        <div>
                            <div class="font-extrabold">{{ $result->skill->title }}</div>
                            <div class="text-[13px] text-muted">
                                {{ $result->outcome === \App\Enums\SkillOutcome::InsufficientData
                                    ? 'В тесте мало вопросов по навыку — уточните его на собеседовании или в следующей версии теста.'
                                    : 'Правильно '.$result->points.' из '.$result->max_points.' баллов. Повторите тему перед следующей попыткой.' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-miniapp.section>
    @endif

    <x-miniapp.section>
        <a href="{{ route('miniapp.career') }}" wire:navigate class="btn-lime min-h-[52px] w-full">Выбрать практику <x-miniapp.icon name="arrow" :size="18" /></a>
        <p class="mt-2 text-2xs text-muted">Результат прикладывается к заявке только с вашего согласия.</p>
    </x-miniapp.section>
</div>
