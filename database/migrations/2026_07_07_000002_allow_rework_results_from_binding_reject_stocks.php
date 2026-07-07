<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rework_results', function (Blueprint $table) {
            $table->foreignId('binding_reject_stock_id')
                ->nullable()
                ->after('production_entry_id')
                ->constrained('binding_reject_stocks')
                ->restrictOnDelete();
            $table->foreignId('production_entry_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rework_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('binding_reject_stock_id');
            $table->foreignId('production_entry_id')->nullable(false)->change();
        });
    }
};
