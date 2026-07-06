<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rework_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_entry_id')->constrained()->restrictOnDelete();
            $table->date('result_date');
            $table->string('component', 20);
            $table->unsignedInteger('qty');
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            $table->string('reject_notes', 500);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rework_results'); }
};
