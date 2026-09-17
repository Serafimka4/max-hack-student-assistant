<?php

namespace App\Livewire\MiniApp;

use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\Assessment;
use App\Support\Schedule\AcademicCalendar;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Главная')]
class Home extends Component
{
    use InteractsWithStudent;

    public function render(AcademicCalendar $calendar): View
    {
        $student = $this->student();
        $now = $calendar->now();
        $group = $student->group;

        $application = $this->user()->applications()
            ->whereNot('status', 'withdrawn')
            ->with(['offer', 'history'])
            ->latest('updated_at')
            ->first();

        $deadline = $student->organization->internshipOffers()->published()
            ->whereDate('apply_until', '>=', $now->toDateString())
            ->orderBy('apply_until')
            ->first();

        return view('livewire.miniapp.home', [
            'student' => $student,
            'now' => $now,
            'parity' => $calendar->parity($now),
            'current' => $group ? $calendar->currentOrNext($group->loadMissing('lessons'), $now) : null,
            'application' => $application,
            'deadline' => $deadline,
            'offersCount' => $student->organization->internshipOffers()->published()->count(),
            'assessment' => Assessment::whereHas('publishedVersion')->with('publishedVersion')->withCount('versions')->first(),
        ]);
    }
}
