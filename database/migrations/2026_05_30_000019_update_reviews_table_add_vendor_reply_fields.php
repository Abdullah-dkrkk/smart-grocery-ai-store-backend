<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'order_id')) {
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete()->after('product_id');
            }
            if (!Schema::hasColumn('reviews', 'vendor_reply')) {
                $table->text('vendor_reply')->nullable()->after('comment');
            }
            if (!Schema::hasColumn('reviews', 'vendor_replied_at')) {
                $table->timestamp('vendor_replied_at')->nullable()->after('vendor_reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'vendor_reply', 'vendor_replied_at']);
        });
    }
};
