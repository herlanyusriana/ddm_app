<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('size_variants', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->string('production_code', 1)->nullable()->after('code');
            $table->decimal('point', 8, 2)->nullable()->after('production_code');
            $table->unique(['production_code', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('size_variants', function (Blueprint $table) {
            $table->dropUnique(['production_code', 'code']);
            $table->dropColumn(['production_code', 'point']);
            $table->unique('code');
        });
    }
};
