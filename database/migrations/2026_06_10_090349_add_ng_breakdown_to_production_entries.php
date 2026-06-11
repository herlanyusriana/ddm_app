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
            $table->integer('repairable_qty')->default(0)->after('good_qty');
            $table->integer('scrap_qty')->default(0)->after('repairable_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropColumn(['repairable_qty', 'scrap_qty']);
        });
    }
};
