<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'badge')) {
                $table->string('badge')->nullable()->after('weight_kg');
            }
            if (!Schema::hasColumn('products', 'short_description')) {
                $table->string('short_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->nullable()->after('short_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['badge', 'short_description', 'unit']);
        });
    }
};
