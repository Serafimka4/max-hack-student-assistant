<div>
    @if ($open)
        <div class="fixed inset-0 z-40 flex animate-fade items-end justify-center bg-ink/55" wire:click.self="$set('open', false)">
            <form wire:submit="submit" role="dialog" aria-modal="true" aria-labelledby="report-title"
                class="w-full max-w-[440px] animate-rise rounded-t-3xl bg-white px-4 pb-[calc(20px+env(safe-area-inset-bottom))] pt-2.5">
                <div class="mx-auto mb-4 h-1 w-10 rounded bg-line"></div>
                <div class="flex items-start justify-between gap-3">
                    <h2 id="report-title" class="heading text-[22px]">{{ $title }}</h2>
                    <button type="button" wire:click="$set('open', false)" class="icon-btn border border-line bg-white" aria-label="Закрыть">
                        <x-miniapp.icon name="close" :size="18" />
                    </button>
                </div>

                <div class="card mt-3.5 bg-paper p-3">
                    <div class="eyebrow text-muted">Контекст сохранится</div>
                    <div class="mt-1 font-bold">{{ $context }}</div>
                </div>

                <textarea wire:model="body" rows="4" placeholder="Опишите, что не так" autofocus
                    class="mt-3 w-full resize-y rounded-2xl border border-line bg-paper px-3.5 py-3 outline-none focus:border-ink"></textarea>
                @error('body')<p class="mt-1 text-[13px] text-danger">{{ $message }}</p>@enderror

                <div class="mb-4 mt-2.5 flex items-start gap-2 text-[13px] text-muted">
                    <x-miniapp.icon name="lock" :size="15" class="mt-0.5" />
                    <span>Конфиденциально, не анонимно. Видят: {{ $assignee }} и координатор.</span>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="btn-ink w-full">Отправить обращение</button>
            </form>
        </div>
    @endif
</div>
