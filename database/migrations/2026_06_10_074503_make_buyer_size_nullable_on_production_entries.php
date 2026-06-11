<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable()->change();
            $table->foreignId('size_variant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable(false)->change();
            $table->foreignId('size_variant_id')->nullable(false)->change();
        });
    }
};
