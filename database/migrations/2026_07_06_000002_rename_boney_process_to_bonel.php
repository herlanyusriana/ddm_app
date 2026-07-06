<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('processes')
            ->where('name', 'Spring/Boney')
            ->update(['name' => 'Spring/Bonel']);
    }

    public function down(): void
    {
        DB::table('processes')
            ->where('name', 'Spring/Bonel')
            ->update(['name' => 'Spring/Boney']);
    }
};
