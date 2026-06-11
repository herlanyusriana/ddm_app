<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $table->date('spk_date')->nullable()->after('spk_no');
            $table->string('dept')->nullable()->after('spk_date');
            $table->string('month', 20)->nullable()->after('dept');
            $table->string('po_no')->nullable()->after('buyer_id');
            $table->string('item')->nullable()->after('po_no');
            $table->string('style')->nullable()->after('item');
            $table->string('remarks')->nullable()->after('target_qty');
            $table->string('shift', 8)->nullable()->after('remarks');
            $table->foreignId('part_id')->nullable()->change();
            $table->foreignId('size_variant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $table->dropColumn(['spk_date', 'dept', 'month', 'po_no', 'item', 'style', 'remarks', 'shift']);
            $table->foreignId('part_id')->nullable(false)->change();
            $table->foreignId('size_variant_id')->nullable(false)->change();
        });
    }
};
