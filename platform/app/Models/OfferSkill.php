<?php

namespace App\Models;

use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OfferSkill extends Pivot
{
    protected $table = 'internship_offer_skill';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['level' => SkillLevel::class];
    }
}
