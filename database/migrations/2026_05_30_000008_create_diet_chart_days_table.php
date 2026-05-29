<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_chart_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_chart_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number');
            $table->json('meals');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_chart_days');
    }
};
