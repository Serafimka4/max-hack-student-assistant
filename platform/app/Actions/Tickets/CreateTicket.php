<?php

namespace App\Actions\Tickets;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\DocumentTemplate;
use App\Models\FaqItem;
use App\Models\InternshipApplication;
use App\Models\Lesson;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Создаёт обращение с контекстом. Контекст и ответственный определяются на сервере по связанному объекту,
 * а не по тексту от клиента.
 */
class CreateTicket
{
    public const SUBJECTS = [
        'lesson' => Lesson::class,
        'faq_item' => FaqItem::class,
        'document_template' => DocumentTemplate::class,
        'application' => InternshipApplication::class,
    ];

    public function __invoke(User $user, TicketCategory $category, string $body, ?string $subjectType = null, ?int $subjectId = null): Ticket
    {
        $student = $user->student;

        if (! $student) {
            throw new DomainRuleException('Обращения доступны студентам вуза-участника пилота.');
        }

        [$subject, $context, $assignee] = $this->describe($user, $category, $subjectType, $subjectId);

        $ticket = new Ticket([
            'organization_id' => $student->organization_id,
            'user_id' => $user->id,
            'category' => $category->value,
            'title' => str($body)->squish()->limit(120)->value() ?: $category->getLabel(),
            'body' => $body,
            'context' => $context,
            'assignee' => $assignee,
            'status' => TicketStatus::New,
            'due_at' => now()->addWeekdays(3),
        ]);
        $ticket->subject()->associate($subject);
        $ticket->save();

        return $ticket;
    }

    /**
     * Связанный объект, текст контекста и ответственный — для предпросмотра и сохранения.
     *
     * @return array{0: ?Model, 1: string, 2: string}
     */
    public function describe(User $user, TicketCategory $category, ?string $type, ?int $id): array
    {
        if (! $user->student) {
            throw new DomainRuleException('Обращения доступны студентам вуза-участника пилота.');
        }

        if ($type === null) {
            return [null, $category->getLabel(), $category->defaultAssignee()];
        }

        $class = self::SUBJECTS[$type] ?? throw new DomainRuleException('Неизвестный объект обращения.');
        $subject = $class::findOrFail($id);
        $organizationId = $user->student->organization_id;

        return match (true) {
            $subject instanceof Lesson => $subject->group->organization_id === $organizationId
                ? [$subject, "{$subject->title}, пара {$subject->number} · {$subject->group->name}", 'Учебный отдел']
                : throw new AuthorizationException,
            $subject instanceof FaqItem => $subject->organization_id === $organizationId
                ? [$subject, "FAQ · {$subject->question}", $subject->owner ?: $category->defaultAssignee()]
                : throw new AuthorizationException,
            $subject instanceof DocumentTemplate => $subject->organization_id === $organizationId && $subject->is_published
                ? [$subject, "Шаблон · {$subject->title}", $subject->department ?: $category->defaultAssignee()]
                : throw new AuthorizationException,
            $subject instanceof InternshipApplication => $subject->user_id === $user->id
                ? [$subject, "Заявка · {$subject->offer->company_name}, {$subject->offer->title}", 'Центр карьеры']
                : throw new AuthorizationException,
        };
    }
}
