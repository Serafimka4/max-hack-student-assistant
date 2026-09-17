<?php

namespace Database\Seeders;

use App\Actions\Assessments\CreateAssessmentVersion;
use App\Actions\Assessments\PublishAssessmentVersion;
use App\Enums\MemberRole;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Демонстрационные данные. Все организации и люди вымышлены; учётные записи — только для проверки.
 */
class DemoSeeder extends Seeder
{
    public function run(CreateAssessmentVersion $createVersion, PublishAssessmentVersion $publish): void
    {
        $university = Organization::create([
            'type' => OrganizationType::University, 'name' => 'Демо-вуз: Институт прикладных ИТ',
            'short_name' => 'Демо-вуз', 'slug' => 'demo-university',
        ]);
        $employer = Organization::create([
            'type' => OrganizationType::Employer, 'name' => 'Демо-работодатель «Волга Софт»',
            'short_name' => 'Волга Софт', 'slug' => 'demo-employer',
        ]);

        User::create(['name' => 'Администратор платформы', 'email' => 'admin@demo.test', 'password' => 'password'])
            ->forceFill(['is_platform_admin' => true])->save();

        User::create(['name' => 'Редактор вуза', 'email' => 'editor@demo.test', 'password' => 'password'])
            ->organizations()->attach($university, ['role' => MemberRole::Editor]);

        User::create(['name' => 'HR работодателя', 'email' => 'hr@demo.test', 'password' => 'password'])
            ->organizations()->attach($employer, ['role' => MemberRole::Admin]);

        $skills = collect(['HTML / CSS', 'JavaScript', 'HTTP и API', 'Git'])
            ->mapWithKeys(fn (string $title) => [$title => Skill::create(['direction' => 'Frontend-разработка', 'title' => $title])]);

        $assessment = $employer->assessments()->create([
            'title' => 'Диагностика frontend-стажёра (демо)',
            'direction' => 'Frontend-разработка',
            'description' => 'Короткая первичная диагностика: чтение кода, поиск ошибки, выбор решения.',
            'duration_minutes' => 30,
            'retake_after_days' => 14,
        ]);

        $version = $createVersion($assessment, notes: 'Первая версия', questions: [
            [
                'skill_id' => $skills['JavaScript']->id, 'type' => 'single', 'points' => 1,
                'prompt' => 'Что выведет код?',
                'code' => 'console.log([1, 2, 3].map(n => n * 2).at(-1));',
                'options' => [['key' => 'A', 'text' => '3'], ['key' => 'B', 'text' => '6'], ['key' => 'C', 'text' => 'undefined']],
                'correct_keys' => ['B'],
            ],
            [
                'skill_id' => $skills['HTTP и API']->id, 'type' => 'single', 'points' => 1,
                'prompt' => 'Какой код ответа означает, что ресурс создан?',
                'options' => [['key' => 'A', 'text' => '200'], ['key' => 'B', 'text' => '201'], ['key' => 'C', 'text' => '204']],
                'correct_keys' => ['B'],
            ],
            [
                'skill_id' => $skills['HTML / CSS']->id, 'type' => 'multiple', 'points' => 2,
                'prompt' => 'Какие свойства участвуют в построении flex-раскладки?',
                'options' => [['key' => 'A', 'text' => 'justify-content'], ['key' => 'B', 'text' => 'float'], ['key' => 'C', 'text' => 'flex-direction']],
                'correct_keys' => ['A', 'C'],
            ],
        ]);
        $publish($version);

        $template = $university->documentTemplates()->create([
            'title' => 'Заявление на практику (демо)',
            'category' => 'Практика',
            'department' => 'Центр карьеры',
            'purpose' => 'Для направления на производственную практику в организацию-партнёр.',
            'instructions' => 'Заполните ФИО, группу, место и сроки практики. Подпишите у руководителя практики от кафедры.',
            'submission' => 'Сдать в центр карьеры не позднее чем за 2 недели до начала практики.',
            'is_published' => true,
        ]);

        $path = "templates/{$university->id}/{$template->id}/v1-zayavlenie-na-praktiku.pdf";
        $disk = config('filesystems.templates_disk');
        Storage::disk($disk)->put($path, $this->demoPdf());
        $template->versions()->create([
            'version' => 1, 'disk' => $disk, 'path' => $path, 'original_name' => 'zayavlenie-na-praktiku.pdf',
            'mime_type' => 'application/pdf', 'size' => Storage::disk($disk)->size($path),
        ]);
    }

    /** Минимальный одностраничный PDF-заглушка. */
    private function demoPdf(): string
    {
        $stream = 'BT /F1 18 Tf 72 760 Td (Demo template: internship application) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
