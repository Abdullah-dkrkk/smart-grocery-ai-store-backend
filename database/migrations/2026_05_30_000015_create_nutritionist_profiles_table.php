<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutritionist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutritionist_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('specialization')->nullable();
            $table->string('qualifications')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('profile_image')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutritionist_profiles');
    }
};
