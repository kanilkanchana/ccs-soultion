<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Null preserves legacy invoices whose original POS checkbox was not stored.
            $table->boolean('is_credit_sale')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['is_credit_sale']);
            $table->dropColumn('is_credit_sale');
        });
    }
};
