<?php

namespace App\Actions\Faq;

use App\Models\FaqFeedback;
use App\Models\FaqItem;
use App\Models\User;

class RateFaqItem
{
    /** Одна оценка на пользователя: повторная заменяет предыдущую. */
    public function __invoke(FaqItem $item, User $user, bool $helpful): FaqFeedback
    {
        return FaqFeedback::updateOrCreate(
            ['faq_item_id' => $item->id, 'user_id' => $user->id],
            ['helpful' => $helpful],
        );
    }
}
