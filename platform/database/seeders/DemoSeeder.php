<?php

namespace Database\Seeders;

use App\Actions\Assessments\CreateAssessmentVersion;
use App\Actions\Assessments\PublishAssessmentVersion;
use App\Actions\Assessments\SaveVersionQuestions;
use App\Actions\Attempts\SaveAnswer;
use App\Actions\Attempts\StartAttempt;
use App\Actions\Attempts\SubmitAttempt;
use App\Actions\Practical\ReviewPractical;
use App\Actions\Practical\SubmitPractical;
use App\Enums\ApplicationStatus;
use App\Enums\LessonKind;
use App\Enums\MemberRole;
use App\Enums\OfferDirection;
use App\Enums\OrganizationType;
use App\Enums\SkillLevel;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Enums\WeekParity;
use App\Models\Assessment;
use App\Models\InternshipOffer;
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

        User::create(['name' => 'Координатор практики', 'email' => 'coordinator@demo.test', 'password' => 'password'])
            ->organizations()->attach($university, ['role' => MemberRole::Coordinator]);

        User::create(['name' => 'HR работодателя', 'email' => 'hr@demo.test', 'password' => 'password'])
            ->organizations()->attach($employer, ['role' => MemberRole::Admin]);

        User::create(['name' => 'Проверяющий специалист', 'email' => 'reviewer@demo.test', 'password' => 'password'])
            ->organizations()->attach($employer, ['role' => MemberRole::Reviewer]);

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

        $q = fn (string $skill, string $type, string $prompt, array $options, array $correct, ?string $code = null, int $points = 1) => [
            'skill_id' => $skills[$skill]->id, 'type' => $type, 'prompt' => $prompt, 'code' => $code, 'points' => $points,
            'options' => collect($options)->map(fn ($text, $key) => ['key' => $key, 'text' => $text])->values()->all(),
            'correct_keys' => $correct,
        ];

        $version = $createVersion($assessment, notes: 'Первая версия (демо)', questions: [
            $q('HTML / CSS', 'multiple', 'Какие свойства участвуют в построении flex-раскладки?',
                ['A' => 'justify-content', 'B' => 'float', 'C' => 'flex-direction'], ['A', 'C'], points: 2),
            $q('HTML / CSS', 'single', 'Какой элемент семантически подходит для основной навигации сайта?',
                ['A' => '<div class="nav">', 'B' => '<nav>', 'C' => '<menuitem>'], ['B']),
            $q('JavaScript', 'single', 'Что выведет код?', ['A' => '3', 'B' => '6', 'C' => 'undefined'], ['B'],
                'console.log([1, 2, 3].map(n => n * 2).at(-1));'),
            $q('JavaScript', 'single', 'В чём ошибка?', ['A' => 'fetch не возвращает промис', 'B' => 'Нет await перед response.json()', 'C' => 'Ошибки нет'], ['B'],
                "async function load() {\n  const response = await fetch('/api/items');\n  const data = response.json();\n  return data.items;\n}"),
            $q('HTTP и API', 'single', 'Какой код ответа означает, что ресурс создан?', ['A' => '200', 'B' => '201', 'C' => '204'], ['B']),
            $q('HTTP и API', 'multiple', 'Какие методы HTTP идемпотентны?', ['A' => 'GET', 'B' => 'POST', 'C' => 'PUT', 'D' => 'DELETE'], ['A', 'C', 'D']),
            $q('Git', 'single', 'Какая команда создаёт новую ветку и переключается на неё?',
                ['A' => 'git branch -m feature', 'B' => 'git switch -c feature', 'C' => 'git checkout feature'], ['B']),
            $q('Git', 'single', 'Что делает git rebase main, выполненный в ветке feature?',
                ['A' => 'Переносит коммиты feature поверх main', 'B' => 'Сливает feature в main', 'C' => 'Удаляет ветку main'], ['A']),
        ]);
        app(SaveVersionQuestions::class)($version, $version->questions->map->only(
            ['skill_id', 'type', 'prompt', 'code', 'options', 'correct_keys', 'points'],
        )->all(), null, [
            'practical_task' => "Сверстайте страницу списка вакансий по макету и загрузите данные из /api/vacancies.\n"
                .'Покажите состояние загрузки и ошибку, если API недоступно. Решение — ссылка на репозиторий с README.',
            'practical_rubric' => [
                ['skill_id' => $skills['HTML / CSS']->id, 'criterion' => 'Вёрстка адаптивна на ширине 375–1440 px', 'max_points' => 4],
                ['skill_id' => $skills['HTML / CSS']->id, 'criterion' => 'Семантическая разметка и доступность', 'max_points' => 2],
                ['skill_id' => $skills['JavaScript']->id, 'criterion' => 'Данные загружаются асинхронно, состояние загрузки отображается', 'max_points' => 4],
                ['skill_id' => $skills['HTTP и API']->id, 'criterion' => 'Обработаны ошибки ответа API', 'max_points' => 4],
                ['skill_id' => $skills['Git']->id, 'criterion' => 'Осмысленная история коммитов', 'max_points' => 2],
            ],
        ]);
        $publish($version);

        $student = $this->seedStudentServices($university, $employer, $skills->all());
        $this->seedDemoAttempt($assessment, $student);

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

    /** Завершённая демо-попытка, чтобы профиль навыков был заполнен. Помечена как демо в названии теста. */
    private function seedDemoAttempt(Assessment $assessment, User $student): void
    {
        $attempt = app(StartAttempt::class)($assessment->load('publishedVersion'), $student);
        $save = app(SaveAnswer::class);
        $questions = $attempt->version->questions()->with('skill')->get();

        // HTML/CSS и HTTP — верно, JavaScript — наполовину, Git — без ответов. Дата в прошлом, чтобы повторная попытка была доступна на демонстрации.
        foreach ($questions as $question) {
            $keys = match ($question->skill->title) {
                'HTML / CSS', 'HTTP и API' => $question->correct_keys,
                'JavaScript' => $question->position === 3 ? $question->correct_keys : ['A'],
                default => null,
            };
            $keys && $save($attempt, $student, $question->id, $keys);
        }

        app(SubmitAttempt::class)($attempt);
        $attempt->forceFill(['started_at' => now()->subDays(20), 'submitted_at' => now()->subDays(20)->addMinutes(18)])->save();

        // Решение проверено специалистом: HTML/CSS и HTTP — «Прикладной», JavaScript и Git без уровня (не подтверждены вопросами).
        $submission = app(SubmitPractical::class)($attempt->fresh(), $student, 'https://example.com/demo/vacancies', 'Страница вакансий на Vite, fetch с обработкой ошибок.');
        $reviewer = User::where('email', 'reviewer@demo.test')->firstOrFail();
        app(ReviewPractical::class)($submission, $reviewer, [4, 1, 2, 3, 1], 'Хорошая адаптивная вёрстка. Стоит добавить семантические заголовки и повторную попытку загрузки.');
        $submission->forceFill(['submitted_at' => now()->subDays(19), 'reviewed_at' => now()->subDays(17)])->save();

        // Второй демо-студент: решение ждёт проверки — видно в разделе «Проверка практики».
        $second = User::create(['name' => 'Мария Демо', 'email' => 'student2@demo.test']);
        $second->student()->create(['organization_id' => $student->student->organization_id]);
        $other = app(StartAttempt::class)($assessment, $second);
        foreach ($other->version->questions as $question) {
            $save($other, $second, $question->id, $question->correct_keys);
        }
        app(SubmitAttempt::class)($other);
        app(SubmitPractical::class)($other->fresh(), $second, 'https://example.com/demo/vacancies-maria', null);

        // Отклик с разрешёнными результатами: виден в кабинете работодателя.
        $frontend = InternshipOffer::where('title', 'Frontend-стажёр')->firstOrFail();
        $application = $frontend->applications()->create([
            'user_id' => $second->id, 'status' => ApplicationStatus::Submitted, 'share_results' => true,
        ]);
        $application->history()->create(['status' => ApplicationStatus::Submitted, 'created_at' => now()->subDays(2)]);
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
