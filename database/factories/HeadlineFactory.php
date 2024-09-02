<?php

namespace Database\Factories;

use App\Models\Headline;
use Illuminate\Database\Eloquent\Factories\Factory;

class HeadlineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->name(),
            'category' => fake()->randomElement(Headline::CATEGORIES),
            'description' => fake()->text(),
        ];
    }
}
