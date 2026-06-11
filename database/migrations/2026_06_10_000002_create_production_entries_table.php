<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->string('shift', 8);
            $table->foreignId('buyer_id')->constrained();
            $table->foreignId('part_id')->nullable()->constrained();
            $table->foreignId('size_variant_id')->constrained();
            $table->foreignId('process_id')->constrained('processes');
            $table->unsignedInteger('good_qty')->default(0);
            $table->unsignedInteger('ng_qty')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['production_date', 'shift', 'process_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entries');
    }
};
