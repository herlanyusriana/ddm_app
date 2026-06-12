<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\Part;
use App\Models\Process;
use App\Models\Spk;
use App\Models\SizeVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductionEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'production_date' => now()->toDateString(),
            'spk_id' => Spk::factory(),
            'shift' => '1',
            'buyer_id' => Buyer::factory(),
            'part_id' => Part::factory(),
            'size_variant_id' => SizeVariant::factory(),
            'process_id' => Process::factory(),
            'good_qty' => $this->faker->numberBetween(0, 200),
            'ng_qty' => $this->faker->numberBetween(0, 10),
            'notes' => null,
        ];
    }
}
