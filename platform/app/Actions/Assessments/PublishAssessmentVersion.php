<?php

namespace App\Actions\Assessments;

use App\Exceptions\DomainRuleException;
use App\Models\AssessmentVersion;

class PublishAssessmentVersion
{
    public function __invoke(AssessmentVersion $version): AssessmentVersion
    {
        if ($version->isPublished()) {
            return $version;
        }

        if (! $version->questions()->exists()) {
            throw new DomainRuleException('Нельзя опубликовать версию без вопросов.');
        }

        $version->forceFill(['published_at' => now()])->save();

        return $version;
    }
}
