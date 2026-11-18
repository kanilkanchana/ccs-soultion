<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('warranty_rules',function(Blueprint $t){$t->string('coverage_kind',30)->default('standard');$t->unsignedSmallInteger('free_service_count')->default(0);});
  Schema::table('warranties',function(Blueprint $t){$t->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->restrictOnDelete();});
  Schema::create('warranty_service_visits',function(Blueprint $t){$t->id();$t->foreignId('warranty_id')->constrained('warranties')->restrictOnDelete();$t->foreignId('repair_job_id')->constrained('repair_jobs')->restrictOnDelete();$t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();$t->date('service_date');$t->unsignedInteger('mileage_km')->nullable();$t->text('notes')->nullable();$t->timestamps();$t->unique(['warranty_id','repair_job_id'],'warranty_service_job_unique');});
 }
 public function down(): void {
  Schema::dropIfExists('warranty_service_visits');
  Schema::table('warranties',function(Blueprint $t){$t->dropForeign(['invoice_item_id']);$t->dropColumn('invoice_item_id');});
  Schema::table('warranty_rules',function(Blueprint $t){$t->dropColumn(['coverage_kind','free_service_count']);});
 }
};
