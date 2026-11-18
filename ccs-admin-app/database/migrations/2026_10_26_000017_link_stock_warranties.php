<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('inventory_items',function(Blueprint $t){$t->foreignId('default_warranty_rule_id')->nullable()->constrained('warranty_rules')->restrictOnDelete();});
  Schema::table('invoice_items',function(Blueprint $t){$t->foreignId('sale_warranty_rule_id')->nullable()->constrained('warranty_rules')->restrictOnDelete();$t->text('sale_warranty_snapshot')->nullable();});
  Schema::table('warranties',function(Blueprint $t){$t->foreignId('stock_sale_item_id')->nullable()->unique()->constrained('invoice_items')->restrictOnDelete();});
  Schema::table('warranty_rules',function(Blueprint $t){$t->string('package_key',50)->nullable()->unique();});
  $packages=[
   ['recon_1','Reconditioned battery - 1 year','hybrid_battery','reconditioned_battery',12,0],
   ['recon_2','Reconditioned battery - 2 years','hybrid_battery','reconditioned_battery',24,0],
   ['recon_3','Reconditioned battery - 3 years','hybrid_battery','reconditioned_battery',36,0],
   ['new_3','Brand-new battery - 3 years + 3 free services','hybrid_battery','new_battery',36,3],
   ['module_6','Battery module - 6 months','hybrid_battery','module',6,0],
   ['module_12','Battery module - 1 year','hybrid_battery','module',12,0],
   ['abs_6','ABS pump / module repair - 6 months','abs','abs',6,0],
   ['accumulator_none','Accumulator repair - No warranty','abs','no_warranty',0,0],
  ];
  foreach($packages as [$key,$name,$category,$kind,$months,$services])DB::table('warranty_rules')->insert([
   'package_key'=>$key,'name'=>$name,'category'=>$category,'coverage_kind'=>$kind,'duration_months'=>$months,'distance_km'=>null,'free_service_count'=>$services,
   'limit_basis'=>$kind==='no_warranty'?'custom':'time_first',
   'terms'=>$kind==='no_warranty'?'No warranty is provided for accumulator repair.':($months.'-month warranty from the issue date.'.($services?' Includes '.$services.' free services; scheduling must be agreed with the workshop.':'')),
   'approved_by_owner'=>false,'active'=>false,'created_at'=>now(),'updated_at'=>now(),
  ]);
 }
 public function down(): void {
  Schema::table('warranties',function(Blueprint $t){$t->dropForeign(['stock_sale_item_id']);$t->dropUnique(['stock_sale_item_id']);$t->dropColumn('stock_sale_item_id');});
  Schema::table('invoice_items',function(Blueprint $t){$t->dropForeign(['sale_warranty_rule_id']);$t->dropColumn(['sale_warranty_rule_id','sale_warranty_snapshot']);});
  Schema::table('inventory_items',function(Blueprint $t){$t->dropForeign(['default_warranty_rule_id']);$t->dropColumn('default_warranty_rule_id');});
  Schema::table('warranty_rules',function(Blueprint $t){$t->dropUnique(['package_key']);$t->dropColumn('package_key');});
 }
};
