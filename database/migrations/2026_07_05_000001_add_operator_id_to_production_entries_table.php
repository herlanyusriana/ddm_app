<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->foreignId('operator_id')
                ->nullable()
                ->after('spk_id')
                ->constrained('operators')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_id');
        });
    }
};
