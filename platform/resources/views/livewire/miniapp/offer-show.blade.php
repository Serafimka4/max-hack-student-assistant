<div data-back-url="{{ route('miniapp.career') }}">
    <header class="px-4 pb-6 pt-3.5 text-ink bg-{{ $offer->direction->accent() }}">
        <a href="{{ route('miniapp.career') }}" wire:navigate class="icon-btn bg-ink/10" aria-label="Назад">
            <x-miniapp.icon name="left" />
        </a>
        <div class="eyebrow mt-[22px]">{{ $offer->direction->getLabel() }} · {{ $offer->company_name }}</div>
        <h1 class="heading mt-2 text-[30px]">{{ $offer->title }}</h1>
        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-ink/20 pt-4 text-sm font-bold">
            <div><dt class="text-[11px] font-semibold opacity-65">Формат</dt><dd>{{ $offer->format }}</dd></div>
            <div><dt class="text-[11px] font-semibold opacity-65">Сроки</dt>
                <dd>{{ $offer->starts_on ? $offer->starts_on->translatedFormat('j M').' — '.$offer->ends_on?->translatedFormat('j M') : 'по согласованию' }}</dd></div>
            <div><dt class="text-[11px] font-semibold opacity-65">Мест</dt><dd>{{ $offer->places }}</dd></div>
            <div><dt class="text-[11px] font-semibold opacity-65">Приём</dt>
                <dd>{{ $offer->apply_until ? ($offer->isOpen() ? 'до '.$offer->apply_until->translatedFormat('j M') : 'закрыт') : 'открыт' }}</dd></div>
        </dl>
    </header>

    @if ($application)
        <x-miniapp.section>
            <div class="card border-ink">
                <div class="flex items-center justify-between">
                    <span class="font-extrabold">Ваша заявка</span>
                    <x-miniapp.application-badge :status="$application->status" />
                </div>
                <x-miniapp.status-steps :status="$application->status" class="mt-3.5" />
            </div>
        </x-miniapp.section>
    @endif

    @if ($offer->tasks)
        <x-miniapp.section title="Задачи">
            <div class="card flex flex-col gap-2.5">
                @foreach ($offer->tasks as $i => $task)
                    <div class="flex items-start gap-3">
                        <span class="eyebrow mt-[3px] text-muted">0{{ $i + 1 }}</span>
                        <span>{{ $task }}</span>
                    </div>
                @endforeach
            </div>
        </x-miniapp.section>
    @endif

    @if ($match->isNotEmpty())
        <x-miniapp.section title="Навыки">
            <div class="card p-0">
                @foreach ($match as $i => $row)
                    <div @class(['flex items-center gap-3 px-4 py-3', 'border-t border-line' => $i > 0])>
                        <span @class(['grid size-[26px] flex-none place-items-center rounded-lg',
                            'bg-lime' => $row['state'] === 'ok',
                            'bg-coral text-white' => $row['state'] === 'gap',
                            'border border-dashed border-muted bg-paper text-muted' => $row['state'] === 'unknown'])>
                            <x-miniapp.icon :name="match ($row['state']) { 'ok' => 'check', 'gap' => 'down', default => 'help' }" :size="14" :stroke="2.6" />
                        </span>
                        <div class="flex-1">
                            <div class="font-bold">{{ $row['skill']->title }}</div>
                            <div class="text-2xs text-muted">
                                Нужно: {{ mb_strtolower($row['required']->getLabel()) }} · у вас: {{ $row['mine'] ? mb_strtolower($row['mine']->getLabel()) : 'не оценено' }}
                            </div>
                        </div>
                        <span class="text-2xs font-bold">{{ ['ok' => 'Подтверждено', 'gap' => 'Ниже требуемого', 'unknown' => 'Не оценено'][$row['state']] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-2xs text-muted">Непроверенные навыки работодатель уточнит на собеседовании. Автоматического отказа по тесту нет.</p>
        </x-miniapp.section>
    @endif

    <x-miniapp.section>
        @if ($application)
            <div class="flex flex-col gap-2.5">
                <button type="button" class="btn-ghost w-full"
                    x-on:click="$dispatch('open-report', { category: 'internship', subjectType: 'application', subjectId: {{ $application->id }} })">
                    <x-miniapp.icon name="chat" :size="18" />Нужна помощь координатора
                </button>
                @if ($application->status->canBeWithdrawn())
                    <button type="button" wire:click="withdraw" wire:confirm="Отозвать заявку?" wire:loading.attr="disabled" class="btn w-full text-danger">
                        Отозвать заявку
                    </button>
                @endif
            </div>
        @elseif ($offer->isOpen())
            <label class="card flex cursor-pointer items-center gap-3">
                <div class="flex-1">
                    <div class="font-extrabold">Приложить результаты диагностики</div>
                    <div class="mt-0.5 text-2xs text-muted">Увидит только {{ $offer->company_name }}. Доступ можно отозвать.</div>
                </div>
                <input type="checkbox" wire:model="shareResults"
                    class="relative h-7 w-12 flex-none cursor-pointer appearance-none rounded-full bg-line transition checked:bg-ink
                        after:absolute after:left-[3px] after:top-[3px] after:size-[22px] after:rounded-full after:bg-white after:shadow after:transition
                        checked:after:translate-x-5 checked:after:bg-lime">
            </label>
            <button type="button" wire:click="apply" wire:loading.attr="disabled" class="btn-lime mt-3 min-h-[52px] w-full">
                Подать заявку <x-miniapp.icon name="arrow" :size="18" />
            </button>
        @else
            <div class="card text-[13px] text-muted">Приём заявок закрыт.</div>
        @endif

        @if ($offer->contact)
            <div class="mt-3.5 text-2xs text-muted">Контакт: {{ $offer->contact }}. Обновлено {{ $offer->updated_at->translatedFormat('j M') }}</div>
        @endif
    </x-miniapp.section>
</div>
