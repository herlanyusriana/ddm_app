<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rework_results', function (Blueprint $table) {
            $table->string('component', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rework_results', function (Blueprint $table) {
            $table->string('component', 20)->change();
        });
    }
};
