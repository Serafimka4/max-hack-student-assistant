<div>
    <x-miniapp.header title="Помощь" eyebrow="Вопросы · документы · обращения">
        <div class="mt-5 px-4">
            <label class="flex h-[46px] items-center gap-2.5 rounded-2xl border border-ink-line bg-ink-2 px-3.5 text-white focus-within:border-lime">
                <x-miniapp.icon name="search" :size="18" />
                <input type="search" wire:model.live.debounce.300ms="q" placeholder="Справка, практика, стипендия…" aria-label="Поиск по вопросам"
                    class="flex-1 bg-transparent text-[15px] outline-none placeholder:text-muted-dark">
            </label>
        </div>
    </x-miniapp.header>

    <x-miniapp.section title="Мои обращения">
        <x-slot:action>
            <button type="button" class="link" x-on:click="$dispatch('open-report', { category: 'other' })">Создать <x-miniapp.icon name="right" :size="14" /></button>
        </x-slot:action>

        @if ($tickets->isEmpty())
            <div class="card text-[13px] text-muted">Обращений нет. Сообщить о проблеме можно из расписания, вопроса или заявки — контекст сохранится.</div>
        @else
            <div class="no-scrollbar -mx-4 grid snap-x snap-mandatory auto-cols-[78%] grid-flow-col gap-2.5 overflow-x-auto scroll-px-4 px-4">
                @foreach ($tickets as $ticket)
                    <div class="card flex snap-start flex-col p-3.5" wire:key="ticket-{{ $ticket->id }}">
                        <x-miniapp.ticket-badge :status="$ticket->status" class="self-start" />
                        <div class="mt-2.5 font-extrabold leading-tight">{{ $ticket->title }}</div>
                        <div class="mt-1 text-2xs text-muted">{{ $ticket->context }}</div>
                        @if ($ticket->resolution)
                            <div class="mt-2 rounded-xl bg-paper p-2.5 text-[13px]">{{ $ticket->resolution }}</div>
                        @endif
                        <div class="mt-auto">
                            <div class="my-2.5 h-px bg-line"></div>
                            <div class="flex items-center justify-between text-2xs">
                                <span class="font-bold">{{ $ticket->assignee }}</span>
                                <span class="text-muted">
                                    @if ($ticket->status === \App\Enums\TicketStatus::Resolved)
                                        {{ $ticket->confirmed_at ? 'подтверждено' : 'решено '.$ticket->resolved_at?->translatedFormat('j M') }}
                                    @else
                                        ответ до {{ $ticket->due_at?->translatedFormat('j M') }}
                                    @endif
                                </span>
                            </div>
                            @if ($ticket->status === \App\Enums\TicketStatus::Resolved && ! $ticket->confirmed_at)
                                <button type="button" wire:click="confirm({{ $ticket->id }})" class="btn-lime mt-2.5 min-h-[34px] w-full text-xs">Подтвердить решение</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-miniapp.section>

    <x-miniapp.section title="Частые вопросы">
        <div class="no-scrollbar mb-3 flex gap-1.5 overflow-x-auto pb-0.5">
            <button type="button" wire:click="$set('category', '')" @class(['chip', 'chip-active' => $category === ''])>Все</button>
            @foreach ($categories as $name)
                <button type="button" wire:click="$set('category', @js($name))" @class(['chip', 'chip-active' => $category === $name])>{{ $name }}</button>
            @endforeach
        </div>

        <div class="card p-0" x-data="{ open: null }" wire:loading.class="opacity-60" wire:target="q,category">
            @forelse ($faq as $i => $item)
                <div @class(['border-t border-line' => $i > 0]) wire:key="faq-{{ $item->id }}">
                    <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-[15px] text-left font-bold"
                        x-on:click="open = open === {{ $item->id }} ? null : {{ $item->id }}" :aria-expanded="open === {{ $item->id }}">
                        <span>{{ $item->question }}</span>
                        <span class="grid size-7 flex-none place-items-center rounded-lg transition"
                            :class="open === {{ $item->id }} ? 'rotate-180 bg-lime' : 'bg-paper'">
                            <x-miniapp.icon name="down" :size="16" />
                        </span>
                    </button>
                    <div x-show="open === {{ $item->id }}" x-cloak x-collapse class="px-4 pb-4">
                        <p class="text-sm">{{ $item->answer }}</p>
                        <div class="mt-2 text-2xs text-muted">{{ $item->owner }} · обновлено {{ $item->updated_at->translatedFormat('j M') }}</div>
                        @if ($rated->has($item->id))
                            <div class="mt-3 flex items-center gap-1.5 text-[13px] font-bold"><x-miniapp.icon name="check" :size="16" />Спасибо за оценку</div>
                        @else
                            <div class="mt-3 flex gap-2">
                                <button type="button" wire:click="rate({{ $item->id }}, true)" class="btn-lime min-h-9 text-[13px]">Помогло</button>
                                <button type="button" wire:click="rate({{ $item->id }}, false)" class="btn-ghost min-h-9 text-[13px]">Нужна помощь</button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-4">
                    <p class="text-[13px] text-muted">Ничего не нашлось.</p>
                    <button type="button" class="btn-ink mt-2.5" x-on:click="$dispatch('open-report', { category: 'other' })">Задать вопрос</button>
                </div>
            @endforelse
        </div>
    </x-miniapp.section>

    <x-miniapp.section title="Шаблоны" id="templates">
        <div class="flex flex-col gap-2.5">
            @forelse ($templates as $i => $template)
                @php $version = $template->currentVersion; @endphp
                <div class="card flex items-center gap-3" wire:key="template-{{ $template->id }}">
                    <span class="grid size-11 flex-none place-items-center rounded-xl {{ ['bg-coral', 'bg-lime', 'bg-blue'][$i % 3] }}">
                        <x-miniapp.icon name="file" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="font-extrabold">{{ $template->title }}</div>
                        <div class="text-2xs text-muted">
                            {{ strtoupper(pathinfo($version->original_name, PATHINFO_EXTENSION)) }} · {{ \Illuminate\Support\Number::fileSize($version->size) }}@if ($template->department) · {{ $template->department }}@endif
                        </div>
                    </div>
                    <button type="button" class="icon-btn border border-line bg-white" aria-label="Скачать: {{ $template->title }}"
                        x-on:click="downloadFile(@js($version->temporaryDownloadUrl()), @js($version->original_name))">
                        <x-miniapp.icon name="download" :size="18" />
                    </button>
                </div>
            @empty
                <div class="card text-[13px] text-muted">Шаблоны ещё не опубликованы.</div>
            @endforelse
        </div>
    </x-miniapp.section>
</div>
