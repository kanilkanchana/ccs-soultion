<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('inventory_items',function(Blueprint $t){$t->string('barcode',150)->nullable()->unique();});
  Schema::table('invoice_items',function(Blueprint $t){$t->string('item_origin',30)->default('manual');});
 }
 public function down(): void {
  Schema::table('invoice_items',function(Blueprint $t){$t->dropColumn('item_origin');});
  Schema::table('inventory_items',function(Blueprint $t){$t->dropUnique(['barcode']);$t->dropColumn('barcode');});
 }
};
