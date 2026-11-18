<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasTable('ccs_developer_db_permissions'))Schema::create('ccs_developer_db_permissions',function(Blueprint $t){$t->id();$t->unsignedBigInteger('developer_account_id')->unique();$t->boolean('can_add')->default(false);$t->boolean('can_edit')->default(false);$t->boolean('can_delete')->default(false);$t->timestamps();});
  if(!Schema::hasTable('ccs_developer_db_changes'))Schema::create('ccs_developer_db_changes',function(Blueprint $t){$t->id();$t->uuid('operation_id')->unique();$t->unsignedBigInteger('developer_account_id');$t->string('action',30);$t->string('table_name');$t->unsignedBigInteger('row_id')->nullable();$t->text('before_json')->nullable();$t->text('after_json')->nullable();$t->text('reason');$t->string('backup_id')->nullable();$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('ccs_developer_db_changes');Schema::dropIfExists('ccs_developer_db_permissions');}
};
