<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\TallyUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeagueSession>
 */
class SessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'league_id' => fn () => League::factory()->create(),
            'timezone' => 'Europe/London',
            'starts_at' => fake()->dateTime(),
            'ends_at' => fake()->dateTime(),
            'structure' => [],
        ];
    }
}
