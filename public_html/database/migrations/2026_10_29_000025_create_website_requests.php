<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasTable('ccs_website_requests'))Schema::create('ccs_website_requests',function(Blueprint $t){
   $t->id();$t->uuid('submission_id')->unique();$t->string('public_reference',40)->unique();$t->string('payload_hash',64);$t->string('kind',20)->index();$t->string('status',20)->default('new')->index();
   $t->string('name',150);$t->string('phone',30)->index();$t->string('whatsapp',30)->nullable();$t->string('vehicle',180)->nullable();$t->string('registration_number',30)->nullable()->index();$t->string('chassis',80)->nullable();$t->string('service',150)->nullable();$t->string('reference',120)->nullable();$t->text('message')->nullable();
   $t->date('preferred_date')->nullable();$t->string('preferred_time',5)->nullable();$t->date('confirmed_date')->nullable()->index();$t->string('confirmed_time',5)->nullable();$t->boolean('contact_consent')->default(false);$t->boolean('reminder_consent')->default(false);$t->string('source_page',50);$t->string('source_origin',255);$t->text('internal_notes')->nullable();$t->unsignedBigInteger('customer_id')->nullable();$t->unsignedBigInteger('vehicle_id')->nullable();$t->unsignedBigInteger('repair_job_id')->nullable()->index();$t->unsignedBigInteger('updated_by')->nullable();$t->unsignedInteger('version')->default(1);$t->timestamps();
  });
  if(!Schema::hasTable('ccs_website_settings'))Schema::create('ccs_website_settings',function(Blueprint $t){$t->string('key')->primary();$t->text('value');$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('ccs_website_requests');Schema::dropIfExists('ccs_website_settings');}
};
