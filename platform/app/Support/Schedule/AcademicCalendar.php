<?php

namespace App\Support\Schedule;

use App\Enums\WeekParity;
use App\Models\Lesson;
use App\Models\StudyGroup;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AcademicCalendar
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('miniapp.timezone'));
    }

    /** Нечётная неделя от начала семестра — числитель. */
    public function parity(CarbonImmutable $date): WeekParity
    {
        $start = CarbonImmutable::parse(config('miniapp.semester_start'), config('miniapp.timezone'))->startOfWeek();
        $week = (int) floor($start->diffInDays($date->startOfWeek(), false) / 7) + 1;

        return $week % 2 === 1 ? WeekParity::Numerator : WeekParity::Denominator;
    }

    /** @return list<CarbonImmutable> понедельник–суббота недели, в которую входит дата */
    public function weekDays(CarbonImmutable $date): array
    {
        $monday = $date->startOfWeek();

        return array_map(fn (int $i) => $monday->addDays($i), range(0, 5));
    }

    /** @return Collection<int, Lesson> */
    public function lessonsOn(StudyGroup $group, CarbonImmutable $date): Collection
    {
        $parity = $this->parity($date);

        return $group->lessons
            ->where('weekday', $date->dayOfWeekIso)
            ->filter(fn (Lesson $l) => $l->parity === WeekParity::Every || $l->parity === $parity)
            ->values();
    }

    /**
     * Текущая или ближайшая пара сегодня.
     *
     * @return array{lesson: Lesson, is_now: bool, next: ?Lesson}|null
     */
    public function currentOrNext(StudyGroup $group, ?CarbonImmutable $now = null): ?array
    {
        $now ??= $this->now();
        $lessons = $this->lessonsOn($group, $now);
        $time = $now->format('H:i:s');

        foreach ($lessons as $i => $lesson) {
            if ($time < $lesson->ends_at) {
                return [
                    'lesson' => $lesson,
                    'is_now' => $time >= $lesson->starts_at,
                    'next' => $lessons->get($i + 1),
                ];
            }
        }

        return null;
    }
}
