<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->decimal('height', 5, 1)->nullable()->after('weight');
            $table->decimal('bmi', 4, 1)->nullable()->after('height');
            $table->string('activity_level')->nullable()->after('dietary_type');
            $table->text('medical_conditions')->nullable()->after('activity_level');
            $table->decimal('daily_calorie_target')->nullable()->after('medical_conditions');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->dropColumn(['height', 'bmi', 'activity_level', 'medical_conditions', 'daily_calorie_target', 'deleted_at']);
        });
    }
};
