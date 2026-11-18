
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'nic')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('nic')->nullable();
            });
        }

        if (!Schema::hasColumn('customers', 'address')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->text('address')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid dropping
        // existing customer data or columns.
    }
};
