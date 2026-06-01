<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->text('description')->nullable()->after('code');
            $table->string('applies_to', 20)->default('all')->after('value');
            $table->json('applicable_ids')->nullable()->after('applies_to');
            $table->unsignedInteger('minimum_items')->nullable()->after('applicable_ids');
            $table->unsignedInteger('per_user_limit')->nullable()->after('max_uses');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->after('value');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'applies_to',
                'applicable_ids',
                'minimum_items',
                'per_user_limit',
                'max_discount_amount',
            ]);
            $table->dropIndex(['code']);
            $table->dropIndex(['is_active']);
        });
    }
};
