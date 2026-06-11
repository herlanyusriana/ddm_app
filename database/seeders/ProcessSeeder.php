<?php

namespace Database\Seeders;

use App\Models\Process;
use Illuminate\Database\Seeder;

class ProcessSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Warehouse RM', 'sort_order' => 10, 'is_input_process' => false, 'is_fg_process' => false],
            ['name' => 'Spring/Pocket', 'sort_order' => 20, 'is_input_process' => true, 'is_fg_process' => false],
            ['name' => 'Spring/Boney', 'sort_order' => 30, 'is_input_process' => true, 'is_fg_process' => false],
            ['name' => 'Sewing', 'sort_order' => 40, 'is_input_process' => true, 'is_fg_process' => false],
            ['name' => 'Binding', 'sort_order' => 50, 'is_input_process' => true, 'is_fg_process' => false],
            ['name' => 'Packing', 'sort_order' => 60, 'is_input_process' => true, 'is_fg_process' => true],
        ];

        foreach ($rows as $row) {
            Process::updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
