<?php

namespace App\Livewire\MiniApp;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketCategory;
use App\Exceptions\DomainRuleException;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

/** Нижний лист обращения. Открывается событием open-report из любого экрана. */
class ReportSheet extends Component
{
    use InteractsWithStudent;

    public bool $open = false;

    #[Locked]
    public string $category = 'other';

    #[Locked]
    public ?string $subjectType = null;

    #[Locked]
    public ?int $subjectId = null;

    #[Locked]
    public string $context = '';

    #[Locked]
    public string $assignee = '';

    #[Validate('required|string|min:5|max:2000', as: 'описание')]
    public string $body = '';

    #[On('open-report')]
    public function show(string $category = 'other', ?string $subjectType = null, ?int $subjectId = null): void
    {
        $category = TicketCategory::tryFrom($category) ?? TicketCategory::Other;
        [, $context, $assignee] = app(CreateTicket::class)->describe($this->user(), $category, $subjectType, $subjectId);

        $this->fill([
            'category' => $category->value,
            'subjectType' => $subjectType,
            'subjectId' => $subjectId,
            'context' => $context,
            'assignee' => $assignee,
            'body' => '',
            'open' => true,
        ]);
        $this->resetValidation();
    }

    public function submit(CreateTicket $create): void
    {
        $this->validate();

        try {
            $create($this->user(), TicketCategory::from($this->category), $this->body, $this->subjectType, $this->subjectId);
        } catch (DomainRuleException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->reset('open', 'body');
        $this->toast('Обращение отправлено. Статус — в разделе «Помощь»');
        $this->dispatch('ticket-created');
    }

    public function render(): View
    {
        return view('livewire.miniapp.report-sheet', [
            'title' => TicketCategory::from($this->category)->getLabel(),
        ]);
    }
}
