<?php

namespace Tests\Feature\MiniApp;

use App\Enums\WeekParity;
use App\Livewire\MiniApp\Schedule;
use App\Models\Organization;
use App\Models\User;
use App\Support\Schedule\AcademicCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class ScreensTest extends MiniAppTestCase
{
    public function test_all_screens_render(): void
    {
        $this->actingAs($this->student);

        $this->get('/app')->assertOk()
            ->assertSee('Привет,')
            ->assertSee('Сейчас · до 11:15')
            ->assertSee('Веб-программирование')
            ->assertSee('Аналитик данных');
        $this->get('/app/schedule')->assertOk()->assertSee('Числитель')->assertSee('Операционные системы');
        $this->get('/app/career')->assertOk()->assertSee('Frontend-стажёр');
        $this->get('/app/career?view=skills')->assertOk()->assertSee('Диагностика frontend-стажёра (демо)');
        $this->get('/app/career?view=applications')->assertOk()->assertSee('На рассмотрении');
        $this->get('/app/help')->assertOk()->assertSee('Заявление на практику (демо)')->assertSee('downloadFile(', false)->assertSee('files\\/templates', false);
    }

    public function test_calendar_parity_and_current_lesson(): void
    {
        $calendar = app(AcademicCalendar::class);
        $group = $this->student->student->group->load('lessons');

        $this->assertSame(WeekParity::Numerator, $calendar->parity(CarbonImmutable::parse('2026-09-17')));
        $this->assertSame(WeekParity::Denominator, $calendar->parity(CarbonImmutable::parse('2026-09-24')));

        // Четверг: лабораторная по числителю, проектная — по знаменателю.
        $this->assertSame('Операционные системы', $calendar->lessonsOn($group, CarbonImmutable::parse('2026-09-17'))->first()->title);
        $this->assertSame('Проектная деятельность', $calendar->lessonsOn($group, CarbonImmutable::parse('2026-09-24'))->first()->title);

        $current = $calendar->currentOrNext($group, CarbonImmutable::parse('2026-09-16 09:50', 'Europe/Saratov'));
        $this->assertTrue($current['is_now']);
        $this->assertSame(2, $current['lesson']->number);

        $this->assertNull($calendar->currentOrNext($group, CarbonImmutable::parse('2026-09-16 18:00', 'Europe/Saratov')));
    }

    public function test_student_saves_only_group_of_own_university(): void
    {
        $this->actingAs($this->student);
        $own = $this->student->student->organization->studyGroups()->where('name', 'б-ПИНФ-31')->firstOrFail();
        $foreign = Organization::factory()->create()->studyGroups()->create(['name' => 'Чужая']);

        Livewire::test(Schedule::class)->call('chooseGroup', $own->id)->assertDispatched('toast');
        $this->assertSame($own->id, $this->student->student->fresh()->study_group_id);

        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Schedule::class)->call('chooseGroup', $foreign->id);
    }

    public function test_student_without_access_is_redirected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/app/help')->assertRedirect('/app/start?reason=no-access');
    }
}
