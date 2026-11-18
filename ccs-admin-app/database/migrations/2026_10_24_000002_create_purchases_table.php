<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->string('module_name', 150);
            $table->integer('quantity');
            $table->string('year', 50)->nullable();
            $table->string('condition', 50)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->decimal('price_lkr', 12, 2);
            $table->boolean('is_credit')->default(false);
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};