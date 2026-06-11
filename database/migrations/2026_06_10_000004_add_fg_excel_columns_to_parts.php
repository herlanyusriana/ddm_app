<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('spec')->nullable()->after('name');
            $table->decimal('width_cm', 8, 2)->nullable()->after('spec');
            $table->decimal('depth_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('height_cm', 8, 2)->nullable()->after('depth_cm');
            $table->decimal('cbm_per_unit', 10, 4)->nullable()->after('height_cm');
            $table->decimal('net_weight_pc', 10, 2)->nullable()->after('cbm_per_unit');
            $table->decimal('gross_weight_pc', 10, 2)->nullable()->after('net_weight_pc');
            $table->unsignedInteger('package_box')->nullable()->after('gross_weight_pc');
            $table->string('item_no')->nullable()->after('package_box');
            $table->string('goods_description')->nullable()->after('item_no');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn([
                'spec',
                'width_cm',
                'depth_cm',
                'height_cm',
                'cbm_per_unit',
                'net_weight_pc',
                'gross_weight_pc',
                'package_box',
                'item_no',
                'goods_description',
            ]);
        });
    }
};
