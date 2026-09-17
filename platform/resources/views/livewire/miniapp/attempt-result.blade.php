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
                <span class="badge bg-coral/20 text-coral">Практика не проверялась</span>
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
            Прикладной и уверенный уровни и грейд «Junior» присваиваются только после проверки практического задания специалистом.
            Прохождение без наблюдателя — результат предварительный.
        </p>
    </x-miniapp.section>

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
