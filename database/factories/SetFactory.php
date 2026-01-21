<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Set;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Set>
 */
class SetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'set_num' => fake()->unique()->numerify('#####-1'),
            'name' => fake()->words(3, true),
            'year' => fake()->year(),
            'theme' => fake()->word(),
            'num_parts' => fake()->numberBetween(100, 5000),
            'image_url' => fake()->optional()->imageUrl(),
        ];
    }
}
