<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => fn () => Club::factory()->create(),
            'club_member_id' => 1,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'tel_no' => fake()->phoneNumber(),
        ];
    }
}
