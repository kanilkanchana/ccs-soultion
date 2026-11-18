<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void {
 if(!Schema::hasTable('ccs_website_requests'))throw new RuntimeException('Install Website Requests migration first.');
 foreach(['email','channel'] as $column)if(!Schema::hasColumn('ccs_website_requests',$column))Schema::table('ccs_website_requests',function(Blueprint $t)use($column){$t->string($column,$column==='email'?254:20)->nullable();});
 }public function down():void {foreach(['email','channel'] as $column)if(Schema::hasColumn('ccs_website_requests',$column))Schema::table('ccs_website_requests',fn(Blueprint $t)=>$t->dropColumn($column));}};
