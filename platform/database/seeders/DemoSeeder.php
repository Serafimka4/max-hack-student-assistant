<?php

namespace Database\Seeders;

use App\Actions\Assessments\CreateAssessmentVersion;
use App\Actions\Assessments\PublishAssessmentVersion;
use App\Enums\ApplicationStatus;
use App\Enums\LessonKind;
use App\Enums\MemberRole;
use App\Enums\OfferDirection;
use App\Enums\OrganizationType;
use App\Enums\SkillLevel;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Enums\WeekParity;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\Ticket;
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
        $skills = $skills->merge([
            'SQL' => Skill::create(['direction' => 'Анализ данных', 'title' => 'SQL']),
            'Python' => Skill::create(['direction' => 'Анализ данных', 'title' => 'Python']),
            'Figma' => Skill::create(['direction' => 'Цифровой дизайн', 'title' => 'Figma']),
            'UX-исследования' => Skill::create(['direction' => 'Цифровой дизайн', 'title' => 'UX-исследования']),
        ]);

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

        $this->seedStudentServices($university, $employer, $skills->all());

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

    /**
     * Расписание, студент, FAQ, практики, заявка и обращения для мини-приложения.
     *
     * @param  array<string, Skill>  $skills
     */
    private function seedStudentServices(Organization $university, Organization $employer, array $skills): User
    {
        $group = $university->studyGroups()->create([
            'name' => 'б-ИФСТ-31',
            'schedule_source' => 'Учебный отдел, импорт CSV (демо)',
            'schedule_updated_at' => now()->subDay(),
        ]);
        $university->studyGroups()->create(['name' => 'б-ПИНФ-31']);

        $slots = [1 => ['08:00', '09:30'], 2 => ['09:45', '11:15'], 3 => ['11:35', '13:05'], 4 => ['13:45', '15:15'], 5 => ['15:25', '16:55']];
        $lessons = [
            [1, 1, 'Базы данных', LessonKind::Lecture, 'Кузнецова М. А.', '1/405'],
            [1, 2, 'Базы данных', LessonKind::Lab, 'Кузнецова М. А.', '1/312'],
            [2, 3, 'Теория вероятностей', LessonKind::Lecture, 'Орлов П. В.', '2/201'],
            [2, 4, 'Английский язык', LessonKind::Practice, 'Белова Е. С.', 'Онлайн', true],
            [3, 1, 'Веб-программирование', LessonKind::Lecture, 'Смирнов А. И.', '1/405'],
            [3, 2, 'Веб-программирование', LessonKind::Lab, 'Смирнов А. И.', '1/318'],
            [3, 3, 'Операционные системы', LessonKind::Lecture, 'Громов Д. Н.', '5/110'],
            [4, 2, 'Операционные системы', LessonKind::Lab, 'Громов Д. Н.', '5/214', false, WeekParity::Numerator],
            [4, 2, 'Проектная деятельность', LessonKind::Practice, 'Смирнов А. И.', '1/220', false, WeekParity::Denominator],
            [4, 3, 'Теория вероятностей', LessonKind::Practice, 'Орлов П. В.', '2/305'],
            [5, 1, 'Проектная деятельность', LessonKind::Practice, 'Смирнов А. И.', '1/220'],
            [5, 2, 'Английский язык', LessonKind::Practice, 'Белова Е. С.', 'Онлайн', true],
        ];
        foreach ($lessons as $row) {
            [$weekday, $number, $title, $kind, $teacher, $room] = $row;
            $group->lessons()->create([
                'weekday' => $weekday, 'number' => $number, 'parity' => $row[7] ?? WeekParity::Every,
                'starts_at' => $slots[$number][0], 'ends_at' => $slots[$number][1],
                'title' => $title, 'kind' => $kind, 'teacher' => $teacher, 'room' => $room, 'is_online' => $row[6] ?? false,
            ]);
        }

        $user = User::create(['name' => 'Даниил Демо', 'email' => 'student@demo.test']);
        $user->student()->create(['organization_id' => $university->id, 'study_group_id' => $group->id]);

        $faq = [
            ['Справки', 'Как получить справку об обучении?', 'Закажите справку в деканате (корп. 1, каб. 208). Срок изготовления — до 3 рабочих дней.', 'Деканат'],
            ['Практика', 'Можно ли пройти практику в своей компании?', 'Да, если деятельность компании соответствует направлению подготовки. Нужен договор о практике и согласование с руководителем практики от кафедры.', 'Центр карьеры'],
            ['Учёба', 'Что делать, если пропустил лабораторную?', 'Согласуйте с преподавателем время отработки. При уважительной причине приложите подтверждающий документ.', 'Учебный отдел'],
            ['Контакты', 'Куда обращаться по вопросам стипендии?', 'В стипендиальную комиссию через деканат, часы приёма — Пн–Чт, 10:00–16:00.', 'Деканат'],
        ];
        foreach ($faq as $i => [$category, $question, $answer, $owner]) {
            $university->faqItems()->create(compact('category', 'question', 'answer', 'owner') + ['position' => $i + 1]);
        }

        $offers = [
            [OfferDirection::Development, $employer->id, 'Волга Софт', 'Frontend-стажёр', 'Гибрид', 3, 40,
                ['Вёрстка интерфейсов личного кабинета', 'Интеграция с REST API', 'Исправление ошибок по задачам наставника'],
                ['HTML / CSS' => SkillLevel::Applied, 'JavaScript' => SkillLevel::Basic, 'HTTP и API' => SkillLevel::Basic, 'Git' => SkillLevel::Basic]],
            [OfferDirection::Data, null, 'Энергосбыт (демо)', 'Аналитик данных', 'Офис', 2, 60,
                ['Подготовка отчётов в BI', 'SQL-запросы к данным потребления'],
                ['SQL' => SkillLevel::Applied, 'Python' => SkillLevel::Basic]],
            [OfferDirection::Design, null, 'Студия «Контур» (демо)', 'UX/UI-дизайнер', 'Удалённо', 1, 30,
                ['Прототипы мобильных экранов', 'Юзабилити-интервью'],
                ['Figma' => SkillLevel::Applied, 'UX-исследования' => SkillLevel::Basic]],
        ];
        $created = [];
        foreach ($offers as [$direction, $employerId, $company, $title, $format, $places, $days, $tasks, $requires]) {
            $offer = $university->internshipOffers()->create([
                'employer_id' => $employerId, 'company_name' => $company, 'title' => $title, 'direction' => $direction,
                'format' => $format, 'places' => $places, 'tasks' => $tasks, 'is_published' => true,
                'starts_on' => now()->setDate(now()->year + 1, 7, 1), 'ends_on' => now()->setDate(now()->year + 1, 8, 31),
                'apply_until' => now()->addDays($days), 'contact' => 'Иванова О. П., центр карьеры',
            ]);
            $offer->skills()->attach(collect($requires)->mapWithKeys(fn ($level, $skill) => [$skills[$skill]->id => ['level' => $level]])->all());
            $created[] = $offer;
        }

        $application = $created[1]->applications()->create(['user_id' => $user->id, 'status' => ApplicationStatus::InReview]);
        $application->history()->create(['status' => ApplicationStatus::Submitted, 'created_at' => now()->subDays(9)]);
        $application->history()->create(['status' => ApplicationStatus::InReview, 'created_at' => now()->subDays(7)]);

        $lab = $group->lessons()->where('title', 'Операционные системы')->where('kind', LessonKind::Lab)->first();
        $open = new Ticket([
            'organization_id' => $university->id, 'user_id' => $user->id, 'category' => TicketCategory::Schedule->value,
            'title' => 'Не указана аудитория у лабораторной', 'context' => "{$lab->title}, пара {$lab->number} · {$group->name}",
            'assignee' => 'Учебный отдел', 'status' => TicketStatus::InProgress, 'due_at' => now()->addDay(),
        ]);
        $open->subject()->associate($lab)->save();

        Ticket::create([
            'organization_id' => $university->id, 'user_id' => $user->id, 'category' => TicketCategory::Document->value,
            'title' => 'В шаблоне дневника нет поля для подписи', 'context' => 'Шаблон · Дневник практики',
            'assignee' => 'Кафедра', 'status' => TicketStatus::Resolved, 'resolution' => 'Поле добавлено, загружена новая версия шаблона.',
            'due_at' => now()->subDays(3),
        ])->forceFill(['resolved_at' => now()->subDays(4)])->save();

        return $user;
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
