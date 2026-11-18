<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach(['inventory_items'=>['is_battery_module'],'invoice_items'=>['is_battery_module','requires_serial']] as $table=>$columns)foreach($columns as $column)if(!Schema::hasColumn($table,$column))Schema::table($table,function(Blueprint $t)use($column){$t->boolean($column)->default(false);});
 }
 public function down(): void {
  foreach(['inventory_items'=>['is_battery_module'],'invoice_items'=>['is_battery_module','requires_serial']] as $table=>$columns)foreach($columns as $column)if(Schema::hasColumn($table,$column))Schema::table($table,function(Blueprint $t)use($column){$t->dropColumn($column);});
 }
};
