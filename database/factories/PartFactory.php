<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classification' => 'FG',
            'code' => strtoupper($this->faker->unique()->bothify('P###')),
            'name' => $this->faker->words(2, true),
            'is_active' => true,
        ];
    }
}
