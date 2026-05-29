<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('day');
            $table->string('meal_type');
            $table->string('name');
            $table->integer('calories')->nullable();
            $table->float('protein_g')->nullable();
            $table->float('carbs_g')->nullable();
            $table->float('fat_g')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_meals');
    }
};
