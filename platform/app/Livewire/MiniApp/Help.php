<?php

namespace App\Livewire\MiniApp;

use App\Actions\Faq\RateFaqItem;
use App\Actions\Tickets\ConfirmTicketResolution;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\FaqFeedback;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Помощь')]
class Help extends Component
{
    use InteractsWithStudent;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    public function rate(int $faqItemId, bool $helpful, RateFaqItem $rate): void
    {
        $item = $this->student()->organization->faqItems()->where('is_published', true)->findOrFail($faqItemId);
        $rate($item, $this->user(), $helpful);

        $helpful
            ? $this->toast('Спасибо за оценку')
            : $this->dispatch('open-report', category: 'faq', subjectType: 'faq_item', subjectId: $item->id);
    }

    public function confirm(int $ticketId, ConfirmTicketResolution $confirm): void
    {
        $ticket = $this->user()->tickets()->findOrFail($ticketId);
        $this->perform(fn () => $confirm($ticket, $this->user()), 'Спасибо! Решение подтверждено');
    }

    #[On('ticket-created')]
    public function refreshTickets(): void {}

    public function render(): View
    {
        $organization = $this->student()->organization;
        $search = trim($this->q);

        $faq = $organization->faqItems()->where('is_published', true)
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('question', "%{$search}%")
                ->orWhereLike('answer', "%{$search}%")))
            ->orderBy('position')
            ->get();

        return view('livewire.miniapp.help', [
            'tickets' => $this->user()->tickets()->latest()->get(),
            'categories' => $organization->faqItems()->where('is_published', true)->distinct()->orderBy('category')->pluck('category'),
            'faq' => $faq,
            'rated' => FaqFeedback::where('user_id', $this->user()->id)->pluck('helpful', 'faq_item_id'),
            'templates' => $organization->documentTemplates()->where('is_published', true)
                ->whereHas('currentVersion')->with('currentVersion')->orderBy('title')->get(),
        ]);
    }
}
