<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('qc_label', 40)->nullable()->after('name');
            $table->string('leader_name', 120)->nullable()->after('qc_label');
            $table->unsignedInteger('target_prod')->nullable()->after('leader_name');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['qc_label', 'leader_name', 'target_prod']);
        });
    }
};
