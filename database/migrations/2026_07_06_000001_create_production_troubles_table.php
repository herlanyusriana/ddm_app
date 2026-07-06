<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_troubles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->date('production_date');
            $table->string('shift', 10);
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->string('trouble_type', 40);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('notes', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_troubles');
    }
};
