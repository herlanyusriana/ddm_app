<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binding_reject_stocks', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->string('pallet', 80)->nullable();
            $table->string('po_no', 80)->nullable();
            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
            $table->foreignId('size_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_in')->default(0);
            $table->unsignedInteger('qty_out')->default(0);
            $table->string('paraf', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binding_reject_stocks');
    }
};
