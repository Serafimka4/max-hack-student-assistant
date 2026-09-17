<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['faq_item_id', 'user_id', 'helpful'])]
class FaqFeedback extends Model
{
    protected $table = 'faq_feedback';

    protected function casts(): array
    {
        return ['helpful' => 'boolean'];
    }
}
