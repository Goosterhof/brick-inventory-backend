<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Part;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageOptionPart>
 */
class StorageOptionPartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storage_option_id' => StorageOption::factory(),
            'part_id' => Part::factory(),
            'color_id' => null,
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }
}
