<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutritionist_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutritionist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['nutritionist_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutritionist_clients');
    }
};
