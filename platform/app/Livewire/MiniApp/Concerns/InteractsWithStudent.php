<?php

namespace App\Livewire\MiniApp\Concerns;

use App\Exceptions\DomainRuleException;
use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

trait InteractsWithStudent
{
    protected function user(): User
    {
        /** @var User */
        return Auth::user();
    }

    protected function student(): Student
    {
        return $this->user()->student()->with(['organization', 'group'])->firstOrFail();
    }

    protected function toast(string $message, string $tone = 'success'): void
    {
        $this->dispatch('toast', message: $message, tone: $tone);
    }

    /** Выполняет действие и показывает нарушение предметного правила как сообщение. */
    protected function attempt(Closure $action, string $success): void
    {
        try {
            $action();
            $this->toast($success);
        } catch (DomainRuleException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }
}
