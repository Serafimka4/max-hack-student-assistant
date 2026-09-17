<?php

namespace Tests\Feature\MiniApp;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

abstract class MiniAppTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        // Среда 16.09.2026, 10:00 по Саратову — идёт вторая пара, неделя-числитель.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-16 10:00', 'Europe/Saratov'));
        Carbon::setTestNow(CarbonImmutable::parse('2026-09-16 10:00', 'Europe/Saratov'));
        config(['miniapp.semester_start' => '2026-09-01', 'miniapp.timezone' => 'Europe/Saratov']);

        $this->seed(DemoSeeder::class);
        $this->student = User::where('email', 'student@demo.test')->firstOrFail();
    }
}
