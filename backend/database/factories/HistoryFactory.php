<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\History;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\History>
 */
class HistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => History::generateId(),
            'event_id' => Event::factory(),
            'executed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'memo' => fake()->optional(0.5)->sentence(),
        ];
    }
}
