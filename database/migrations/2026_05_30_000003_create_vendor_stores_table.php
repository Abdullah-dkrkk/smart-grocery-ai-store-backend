<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('store_name');
            $table->text('store_description')->nullable();
            $table->string('store_logo_url')->nullable();
            $table->string('store_banner_url')->nullable();
            $table->text('return_policy')->nullable();
            $table->text('shipping_policy')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_stores');
    }
};
