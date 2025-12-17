<?php

namespace Database\Factories;

use App\Models\Tier;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'league_session_id' => fn () => Session::factory()->create(),
            'tier_id' => fn () => Tier::factory()->create(),
            'contestant_count' => 5,
            'promote_count' => 0,
            'relegate_count' => 0,
        ];
    }
}
