
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchases', 'year')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('year')->nullable();
            });
        }

        if (!Schema::hasColumn('purchases', 'condition')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('condition')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Preserve existing columns and data.
    }
};
