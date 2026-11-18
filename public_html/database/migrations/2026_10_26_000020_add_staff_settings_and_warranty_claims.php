<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasColumn('users','ccs_role'))Schema::table('users',fn(Blueprint $t)=>$t->string('ccs_role',20)->default('legacy'));
  if(!Schema::hasColumn('users','ccs_active'))Schema::table('users',fn(Blueprint $t)=>$t->boolean('ccs_active')->default(true));
  if(!Schema::hasColumn('users','ccs_session_version'))Schema::table('users',fn(Blueprint $t)=>$t->unsignedInteger('ccs_session_version')->default(1));
  if(!Schema::hasTable('ccs_system_settings'))Schema::create('ccs_system_settings',function(Blueprint $t){$t->string('key')->primary();$t->text('value');$t->timestamps();});
  if(!Schema::hasTable('ccs_staff_preferences'))Schema::create('ccs_staff_preferences',function(Blueprint $t){$t->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();$t->text('preferences');$t->timestamps();});
  if(!Schema::hasTable('ccs_notification_reads'))Schema::create('ccs_notification_reads',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->string('notification_key',160);$t->timestamp('read_at');$t->unique(['user_id','notification_key']);});
  if(!Schema::hasTable('warranty_claims'))Schema::create('warranty_claims',function(Blueprint $t){$t->id();$t->foreignId('warranty_id')->constrained('warranties')->restrictOnDelete();$t->foreignId('created_by')->constrained('users')->restrictOnDelete();$t->string('status',20)->default('pending');$t->date('received_on');$t->text('problem');$t->text('findings')->nullable();$t->text('decision_notes')->nullable();$t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();$t->timestamp('decided_at')->nullable();$t->foreignId('repair_job_id')->nullable()->constrained('repair_jobs')->restrictOnDelete();$t->timestamp('completed_at')->nullable();$t->timestamps();$t->index(['status','created_at']);});
  if(!Schema::hasColumn('invoices','print_locale'))Schema::table('invoices',fn(Blueprint $t)=>$t->string('print_locale',10)->nullable());
  if(!Schema::hasColumn('invoices','print_paper'))Schema::table('invoices',fn(Blueprint $t)=>$t->string('print_paper',10)->nullable());
 }
 public function down(): void {Schema::table('invoices',fn(Blueprint $t)=>$t->dropColumn(['print_locale','print_paper']));Schema::dropIfExists('warranty_claims');Schema::dropIfExists('ccs_notification_reads');Schema::dropIfExists('ccs_staff_preferences');Schema::dropIfExists('ccs_system_settings');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['ccs_role','ccs_active','ccs_session_version']));}
};
