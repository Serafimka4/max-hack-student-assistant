<div data-back-url="{{ route('miniapp.career', ['view' => 'skills']) }}">
    <header class="relative overflow-hidden bg-ink px-4 pb-6 pt-3.5 text-white">
        <div class="pointer-events-none absolute -right-24 -top-16 size-56 rounded-full bg-blue opacity-90"></div>
        <a href="{{ route('miniapp.career', ['view' => 'skills']) }}" wire:navigate class="icon-btn relative bg-ink-3" aria-label="Назад">
            <x-miniapp.icon name="left" />
        </a>
        <div class="relative">
            <div class="eyebrow mt-[22px] text-lime">{{ $assessment->direction }}</div>
            <h1 class="heading mt-2 text-[28px]">{{ $assessment->title }}</h1>
            @if ($assessment->description)
                <p class="mt-3 text-sm text-muted-dark">{{ $assessment->description }}</p>
            @endif
            <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-ink-line pt-4 text-sm font-bold">
                <div><dt class="text-[11px] font-semibold text-muted-dark">Вопросов</dt><dd>{{ $version->questions->count() }}</dd></div>
                <div><dt class="text-[11px] font-semibold text-muted-dark">Время</dt><dd>{{ $assessment->duration_minutes ? $assessment->duration_minutes.' мин' : 'без лимита' }}</dd></div>
                <div><dt class="text-[11px] font-semibold text-muted-dark">Версия</dt><dd>v{{ $version->version }}</dd></div>
            </dl>
        </div>
    </header>

    <x-miniapp.section title="Что проверяется">
        <div class="card p-0">
            @foreach ($skills->values() as $i => $row)
                <div @class(['flex items-center justify-between px-4 py-3', 'border-t border-line' => $i > 0])>
                    <span class="font-bold">{{ $row['skill']->title }}</span>
                    <span class="text-[13px] text-muted">{{ trans_choice(':count вопрос|:count вопроса|:count вопросов', $row['count']) }}</span>
                </div>
            @endforeach
        </div>
    </x-miniapp.section>

    <x-miniapp.section title="Правила">
        <div class="card flex flex-col gap-2.5 text-sm">
            <div class="flex gap-2.5"><x-miniapp.icon name="check" :size="18" class="mt-px" /><span>Вопрос засчитывается, если выбраны все правильные варианты и нет лишних.</span></div>
            <div class="flex gap-2.5"><x-miniapp.icon name="target" :size="18" class="mt-px" /><span>Базовый уровень — от {{ $version->basic_threshold }}% по навыку; нужно не меньше {{ $version->min_questions_per_skill }} вопросов на навык.</span></div>
            @if ($version->hasPractical())
                <div class="flex gap-2.5"><x-miniapp.icon name="file" :size="18" class="mt-px" /><span>После вопросов — практическое задание. Его проверяет специалист по рубрике; только оно даёт «Прикладной» уровень (от {{ $version->applied_threshold }}%) и грейд «Junior».</span></div>
            @endif
            <div class="flex gap-2.5"><x-miniapp.icon name="clock" :size="18" class="mt-px" /><span>Ответы сохраняются сразу — можно прерваться и продолжить.@if ($assessment->duration_minutes) По истечении времени попытка завершится с сохранёнными ответами.@endif</span></div>
            <div class="flex gap-2.5"><x-miniapp.icon name="alert" :size="18" class="mt-px" /><span>Повторная попытка — через {{ $assessment->retake_after_days }} дн. Прохождение без наблюдателя: результат предварительный.</span></div>
        </div>
    </x-miniapp.section>

    <x-miniapp.section>
        @if ($open)
            <a href="{{ route('miniapp.attempts.take', $open) }}" wire:navigate class="btn-lime min-h-[52px] w-full">Продолжить попытку <x-miniapp.icon name="arrow" :size="18" /></a>
        @elseif ($retakeAt)
            <div class="card text-sm">
                Повторная попытка будет доступна <b>{{ $retakeAt->timezone(config('miniapp.timezone'))->translatedFormat('j F') }}</b>.
            </div>
        @else
            <button type="button" wire:click="start" wire:loading.attr="disabled" class="btn-lime min-h-[52px] w-full">
                {{ $lastSubmitted ? 'Пройти заново' : 'Начать диагностику' }} <x-miniapp.icon name="arrow" :size="18" />
            </button>
        @endif

        @if ($lastSubmitted)
            <a href="{{ route('miniapp.attempts.result', $lastSubmitted) }}" wire:navigate class="btn-ghost mt-2.5 w-full">
                Результат от {{ $lastSubmitted->submitted_at->timezone(config('miniapp.timezone'))->translatedFormat('j F') }}
            </a>
        @endif
        <p class="mt-3 text-2xs text-muted">Тест подготовлен: {{ $assessment->organization->name }}.</p>
    </x-miniapp.section>
</div>
