<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $table->dropUnique('spks_spk_no_unique');
            $table->index('spk_no');
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $table->dropIndex('spks_spk_no_index');
            $table->unique('spk_no');
        });
    }
};
