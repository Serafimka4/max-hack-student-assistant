<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'type' => OrganizationType::University,
            'name' => $name,
            'short_name' => null,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
        ];
    }

    public function employer(): static
    {
        return $this->state(['type' => OrganizationType::Employer]);
    }
}
