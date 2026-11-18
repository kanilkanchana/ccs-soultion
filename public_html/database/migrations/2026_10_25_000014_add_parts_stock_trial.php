<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('ccs_feature_settings',function(Blueprint $t){$t->string('key')->primary();$t->boolean('enabled')->default(false);});
        DB::table('ccs_feature_settings')->insert(['key'=>'stock_pos','enabled'=>false]);
        Schema::table('inventory_items',function(Blueprint $t){
            $t->boolean('has_serial')->default(false);$t->boolean('active')->default(true);
            $t->string('item_year',50)->nullable();$t->string('supplier',150)->nullable();$t->decimal('purchase_price_lkr',12,2)->nullable();
        });
        Schema::create('stock_units',function(Blueprint $t){
            $t->id();$t->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $t->string('serial_number',100);$t->string('serial_key',100)->unique();
            $t->string('status',20)->default('available')->index();
            $t->foreignId('invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();$t->timestamps();
        });
        Schema::table('invoice_items',function(Blueprint $t){
            $t->foreignId('stock_unit_id')->nullable()->constrained('stock_units')->restrictOnDelete();
            $t->string('serial_number',100)->nullable();$t->boolean('stock_managed')->default(false);
        });
    }
    public function down(): void {
        Schema::table('invoice_items',function(Blueprint $t){$t->dropForeign(['stock_unit_id']);$t->dropColumn(['stock_unit_id','serial_number','stock_managed']);});
        Schema::dropIfExists('stock_units');
        Schema::table('inventory_items',function(Blueprint $t){$t->dropColumn(['has_serial','active','item_year','supplier','purchase_price_lkr']);});
        Schema::dropIfExists('ccs_feature_settings');
    }
};
