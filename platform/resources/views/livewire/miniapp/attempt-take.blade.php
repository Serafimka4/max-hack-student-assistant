@php $total = $questions->count(); $selected = $answers[$question->id] ?? []; @endphp
<div>
    <header class="sticky top-0 z-10 bg-ink px-4 pb-4 pt-3.5 text-white">
        <div class="flex items-center justify-between gap-3">
            <span class="eyebrow text-lime">{{ $attempt->version->assessment->title }}</span>
            @if ($attempt->deadline_at)
                <span class="badge bg-ink-3 text-white tabular-nums" wire:ignore
                    x-data="{ left: {{ max(0, (int) now()->diffInSeconds($attempt->deadline_at, false)) }}, t: null }"
                    x-init="t = setInterval(() => { left = Math.max(0, left - 1); if (left === 0) { clearInterval(t); $wire.finish() } }, 1000)"
                    :class="left < 120 && 'bg-coral text-ink'">
                    <x-miniapp.icon name="clock" :size="12" />
                    <span x-text="String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0')"></span>
                </span>
            @endif
        </div>
        <div class="mt-3 flex items-center justify-between text-[13px] text-muted-dark">
            <span>Вопрос {{ $index + 1 }} из {{ $total }}</span>
            <span>Отвечено {{ $answered }}</span>
        </div>
        <div class="mt-2 grid gap-1" style="grid-template-columns: repeat({{ $total }}, minmax(0, 1fr))">
            @foreach ($questions as $i => $q)
                <button type="button" wire:click="go({{ $i }})" aria-label="Вопрос {{ $i + 1 }}"
                    @class(['h-1.5 rounded-full',
                        'bg-lime' => $i === $index,
                        'bg-white/70' => $i !== $index && ! empty($answers[$q->id] ?? []),
                        'bg-ink-3' => $i !== $index && empty($answers[$q->id] ?? [])])></button>
            @endforeach
        </div>
    </header>

    <x-miniapp.section wire:key="question-{{ $question->id }}">
        <div class="flex items-center gap-2">
            <span class="badge bg-ink/[.07] text-ink">{{ $question->skill->title }}</span>
            <span class="text-2xs text-muted">{{ $question->type === \App\Enums\QuestionType::Multiple ? 'Несколько ответов' : 'Один ответ' }}</span>
        </div>
        <h1 class="mt-3 text-xl font-extrabold leading-snug">{{ $question->prompt }}</h1>
        @if ($question->code)
            <pre class="no-scrollbar mt-3 overflow-x-auto rounded-2xl bg-ink p-4 font-mono text-[13px] leading-relaxed text-lime"><code>{{ $question->code }}</code></pre>
        @endif

        <div class="mt-4 flex flex-col gap-2" role="{{ $question->type === \App\Enums\QuestionType::Multiple ? 'group' : 'radiogroup' }}">
            @foreach ($question->options as $option)
                @php $isOn = in_array((string) $option['key'], $selected, true); @endphp
                <button type="button" wire:click="choose({{ $question->id }}, @js((string) $option['key']))"
                    role="{{ $question->type === \App\Enums\QuestionType::Multiple ? 'checkbox' : 'radio' }}" aria-checked="{{ $isOn ? 'true' : 'false' }}"
                    @class(['flex w-full items-center gap-3 rounded-2xl border p-3.5 text-left transition active:scale-[.99]',
                        'border-ink bg-ink text-white' => $isOn, 'border-line bg-white' => ! $isOn])>
                    <span @class(['grid size-7 flex-none place-items-center font-display text-xs font-bold',
                        $question->type === \App\Enums\QuestionType::Multiple ? 'rounded-lg' : 'rounded-full',
                        'bg-lime text-ink' => $isOn, 'bg-paper' => ! $isOn])>{{ $option['key'] }}</span>
                    <span class="font-semibold">{{ $option['text'] }}</span>
                </button>
            @endforeach
        </div>
        <p class="mt-2 h-4 text-2xs text-muted opacity-0 transition-opacity" wire:loading.class="opacity-100" wire:target="choose" aria-live="polite">Сохраняем…</p>
    </x-miniapp.section>

    <div class="px-4 pt-4">
        <div class="grid grid-cols-2 gap-2.5">
            <button type="button" wire:click="go({{ $index - 1 }})" class="btn-ghost" @disabled($index === 0)>
                <x-miniapp.icon name="left" :size="18" />Назад
            </button>
            @if ($index < $total - 1)
                <button type="button" wire:click="go({{ $index + 1 }})" class="btn-ink">Далее<x-miniapp.icon name="right" :size="18" /></button>
            @else
                <button type="button" wire:click="finish" class="btn-lime"
                    wire:confirm="{{ $answered < $total ? 'Отвечено '.$answered.' из '.$total.'. Завершить? Неотвеченные вопросы не засчитаются.' : 'Завершить диагностику?' }}">
                    Завершить
                </button>
            @endif
        </div>
        @if ($index < $total - 1)
            <button type="button" wire:click="finish" class="link mx-auto mt-4 flex text-muted"
                wire:confirm="Отвечено {{ $answered }} из {{ $total }}. Завершить досрочно? Неотвеченные вопросы не засчитаются.">
                Завершить досрочно
            </button>
        @endif
    </div>
</div>
