<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite may retain columns from a previous failed ALTER sequence.
        // reorder_level is also part of the original workshop schema.
        if (!Schema::hasColumn('inventory_items', 'category')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('category', 80)->nullable();
            });
        }
        if (!Schema::hasColumn('inventory_items', 'service_application')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('service_application', 150)->nullable();
            });
        }
        if (!Schema::hasColumn('inventory_items', 'reorder_level')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unsignedInteger('reorder_level')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Keep reorder_level: migration 000001 owns it and existing stock uses it.
        foreach (['service_application', 'category'] as $column) {
            if (Schema::hasColumn('inventory_items', $column)) {
                Schema::table('inventory_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
