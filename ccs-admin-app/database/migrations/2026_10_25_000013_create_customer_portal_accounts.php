<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_portal_accounts', function(Blueprint $table){
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->restrictOnDelete();
            $table->string('username',80)->unique();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('access_version')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::table('battery_module_readings',function(Blueprint $table){$table->index('serial_number');});
    }
    public function down(): void {
        Schema::table('battery_module_readings',function(Blueprint $table){$table->dropIndex(['serial_number']);});
        Schema::dropIfExists('customer_portal_accounts');
    }
};
