<?php

namespace App\Policies\Concerns;

use App\Models\Organization;
use Filament\Facades\Filament;

trait ResolvesOrganization
{
    /** API передаёт организацию явно, Filament — через текущий кабинет. */
    private function organization(?Organization $organization): ?Organization
    {
        $tenant = Filament::getTenant();

        return $organization ?? ($tenant instanceof Organization ? $tenant : null);
    }
}
