<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {if(!Schema::hasColumn('inventory_items','serial_on_sale'))Schema::table('inventory_items',function(Blueprint $table){$table->boolean('serial_on_sale')->default(false);});}
 public function down():void {Schema::table('inventory_items',fn(Blueprint $table)=>$table->dropColumn('serial_on_sale'));}
};
