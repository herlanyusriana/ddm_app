<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'sort_order' => $this->faker->numberBetween(1, 99),
            'is_input_process' => true,
            'is_fg_process' => false,
        ];
    }
}
