<?php

namespace Database\Factories;

use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'spk_no' => strtoupper($this->faker->unique()->bothify('SPK-###')),
            'spk_date' => now()->toDateString(),
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => now()->format('F'),
            'buyer_id' => Buyer::factory(),
            'po_no' => strtoupper($this->faker->bothify('PO-##-##')),
            'item' => 'Pocket Spring',
            'style' => '12" Queen',
            'target_qty' => 100,
            'remarks' => 'W~24',
            'shift' => '1',
            'status' => 'Pending',
        ];
    }
}
