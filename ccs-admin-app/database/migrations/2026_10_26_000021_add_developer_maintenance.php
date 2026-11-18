<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasTable('ccs_developer_accounts'))Schema::create('ccs_developer_accounts',function(Blueprint $t){$t->id();$t->string('name',150);$t->string('email')->unique();$t->string('password');$t->boolean('active')->default(false);$t->boolean('can_clear_data')->default(false);$t->unsignedInteger('access_version')->default(1);$t->foreignId('authorized_by')->constrained('users')->restrictOnDelete();$t->timestamps();});
  if(!Schema::hasTable('ccs_developer_events'))Schema::create('ccs_developer_events',function(Blueprint $t){$t->id();$t->foreignId('developer_account_id')->nullable()->constrained('ccs_developer_accounts')->nullOnDelete();$t->string('action',100);$t->text('details')->nullable();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('ccs_developer_events');Schema::dropIfExists('ccs_developer_accounts');}
};
