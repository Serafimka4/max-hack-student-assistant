<?php

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class DocumentTemplatePolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->roleIn($organization) !== null;
    }

    public function view(User $user, DocumentTemplate $template): bool
    {
        return $template->is_published || $user->roleIn($template->organization) !== null;
    }

    public function create(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->canManageContentOf($organization);
    }

    public function update(User $user, DocumentTemplate $template): bool
    {
        return $user->canManageContentOf($template->organization);
    }

    public function delete(User $user, DocumentTemplate $template): bool
    {
        return $user->canManageContentOf($template->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }
}
