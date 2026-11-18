<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('item_year', 50)->nullable();
            $table->string('item_condition', 80)->nullable();
            $table->string('item_supplier', 150)->nullable();
        });
    }
    public function down(): void {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['item_year', 'item_condition', 'item_supplier']);
        });
    }
};
