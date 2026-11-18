<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasColumn('ccs_developer_accounts','can_manage_accounts')) {
   Schema::table('ccs_developer_accounts',fn(Blueprint $t)=>$t->boolean('can_manage_accounts')->default(false));
   // Bootstrap the existing primary active operator once; new members cannot delegate access.
   $id=DB::table('ccs_developer_accounts')->where('active',true)->orderBy('id')->value('id');
   if($id)DB::table('ccs_developer_accounts')->where('id',$id)->update(['can_manage_accounts'=>true]);
  }
  if(!Schema::hasTable('ccs_test_imports'))Schema::create('ccs_test_imports',function(Blueprint $t){
   $t->uuid('id')->primary();$t->string('batch_key',24)->unique();$t->date('data_date');
   $t->foreignId('developer_account_id')->constrained('ccs_developer_accounts')->restrictOnDelete();
   $t->string('state',20)->default('imported');$t->text('record_ids');$t->string('fingerprint',64);
   $t->string('backup_id',36);$t->timestamps();
  });
 }
 public function down(): void {Schema::dropIfExists('ccs_test_imports');Schema::table('ccs_developer_accounts',fn(Blueprint $t)=>$t->dropColumn('can_manage_accounts'));}
};
