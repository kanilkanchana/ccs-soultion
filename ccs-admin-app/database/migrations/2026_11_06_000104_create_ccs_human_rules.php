<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration{
public function up():void{
if(!Schema::hasTable('ccs_rule_approvals'))Schema::create('ccs_rule_approvals',function(Blueprint $t){$t->id();$t->string('kind',40);$t->unsignedBigInteger('requested_by');$t->string('fingerprint',64);$t->text('details');$t->string('status',20)->default('pending');$t->unsignedBigInteger('decided_by')->nullable();$t->text('decision_reason')->nullable();$t->timestamp('expires_at')->nullable();$t->timestamp('consumed_at')->nullable();$t->timestamps();$t->index(['requested_by','fingerprint','status']);});
if(!Schema::hasTable('ccs_payment_reversals'))Schema::create('ccs_payment_reversals',function(Blueprint $t){$t->id();$t->unsignedBigInteger('payment_id')->unique();$t->unsignedBigInteger('recorded_by');$t->text('reason');$t->timestamps();});
if(!Schema::hasTable('ccs_invoice_cancellations'))Schema::create('ccs_invoice_cancellations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('invoice_id')->unique();$t->unsignedBigInteger('recorded_by');$t->text('reason');$t->decimal('refund_lkr',12,2)->default(0);$t->boolean('goods_returned')->default(false);$t->string('request_token',36)->unique();$t->text('invoice_snapshot');$t->timestamps();});
if(!Schema::hasTable('ccs_rule_submissions'))Schema::create('ccs_rule_submissions',function(Blueprint $t){$t->id();$t->string('token',36)->unique();$t->unsignedBigInteger('user_id');$t->string('action',200);$t->string('fingerprint',64);$t->text('location');$t->timestamps();});
if(!Schema::hasTable('ccs_rules_mutex'))Schema::create('ccs_rules_mutex',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->unsignedBigInteger('revision')->default(0);});DB::table('ccs_rules_mutex')->insertOrIgnore(['id'=>1,'revision'=>0]);
foreach(['customers','vehicles'] as $table){if(!Schema::hasColumn($table,'rules_key'))Schema::table($table,fn(Blueprint $t)=>$t->string('rules_key',64)->nullable()->unique());if(!Schema::hasColumn($table,'is_system_walkin'))Schema::table($table,fn(Blueprint $t)=>$t->boolean('is_system_walkin')->default(false));}
if(!Schema::hasColumn('customers','email'))Schema::table('customers',fn(Blueprint $t)=>$t->string('email',254)->nullable());
if(!Schema::hasColumn('vehicles','registration_province'))Schema::table('vehicles',fn(Blueprint $t)=>$t->string('registration_province',2)->nullable());
if(!Schema::hasColumn('vehicles','chassis_number'))Schema::table('vehicles',fn(Blueprint $t)=>$t->string('chassis_number',80)->nullable());
if(!Schema::hasColumn('vehicles','plate_kind'))Schema::table('vehicles',fn(Blueprint $t)=>$t->string('plate_kind',20)->nullable());
if(!Schema::hasColumn('repair_jobs','is_system_walkin'))Schema::table('repair_jobs',fn(Blueprint $t)=>$t->boolean('is_system_walkin')->default(false));
if(!Schema::hasColumn('invoices','rules_discount_authorized_by'))Schema::table('invoices',fn(Blueprint $t)=>$t->unsignedBigInteger('rules_discount_authorized_by')->nullable());
if(!Schema::hasColumn('invoices','rules_payload_hash'))Schema::table('invoices',fn(Blueprint $t)=>$t->string('rules_payload_hash',64)->nullable());
if(!Schema::hasColumn('invoices','rules_request_token'))Schema::table('invoices',fn(Blueprint $t)=>$t->string('rules_request_token',36)->nullable()->unique());
}
public function down():void{throw new RuntimeException('Use the pre-install backup to roll back v104. Existing data must not be dropped.');}
};
