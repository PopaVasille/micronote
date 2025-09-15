<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'note_type' => fake()->randomElement(['simple', 'task', 'idea', 'shopping_list', 'reminder']),
            'is_completed' => false,
            'is_favorite' => false,
            'priority' => fake()->numberBetween(1, 5),
            'metadata' => null,
            'version' => 1,
        ];
    }
}
