<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Event::generateId(),
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'category_icon' => fake()->randomElement(Event::CATEGORY_ICONS),
            'last_executed_history_id' => null,
        ];
    }
}
