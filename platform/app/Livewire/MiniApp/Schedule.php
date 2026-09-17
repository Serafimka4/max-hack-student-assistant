<?php

namespace App\Livewire\MiniApp;

use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Support\Schedule\AcademicCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Расписание')]
class Schedule extends Component
{
    use InteractsWithStudent;

    #[Url(as: 'date')]
    public ?string $date = null;

    public function selectDate(string $date): void
    {
        $this->date = CarbonImmutable::createFromFormat('Y-m-d', $date)?->toDateString();
    }

    public function shiftWeek(int $weeks, AcademicCalendar $calendar): void
    {
        $this->date = $this->selected($calendar)->addWeeks(max(-1, min(1, $weeks)))->toDateString();
    }

    /** Выбор и сохранение учебной группы (только групп своего вуза). */
    public function chooseGroup(int $groupId): void
    {
        $student = $this->student();
        $group = $student->organization->studyGroups()->findOrFail($groupId);
        $student->update(['study_group_id' => $group->id]);
        $this->toast("Группа {$group->name} сохранена");
    }

    private function selected(AcademicCalendar $calendar): CarbonImmutable
    {
        $date = $this->date ? CarbonImmutable::createFromFormat('!Y-m-d', $this->date, config('miniapp.timezone')) : null;

        return $date ?: $calendar->now()->startOfDay();
    }

    public function render(AcademicCalendar $calendar): View
    {
        $student = $this->student();
        $group = $student->group?->loadMissing('lessons');
        $now = $calendar->now();
        $selected = $this->selected($calendar);
        $current = $group && $selected->isSameDay($now) ? $calendar->currentOrNext($group, $now) : null;

        return view('livewire.miniapp.schedule', [
            'student' => $student,
            'group' => $group,
            'groups' => $student->organization->studyGroups()->orderBy('name')->get(),
            'now' => $now,
            'selected' => $selected,
            'parity' => $calendar->parity($selected),
            'days' => collect($calendar->weekDays($selected))->map(fn (CarbonImmutable $day) => [
                'date' => $day,
                'count' => $group ? $calendar->lessonsOn($group, $day)->count() : 0,
            ]),
            'lessons' => $group ? $calendar->lessonsOn($group, $selected) : collect(),
            'nowLessonId' => ($current['is_now'] ?? false) ? $current['lesson']->id : null,
        ]);
    }
}
