<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if(!Schema::hasTable('ccs_facebook_sync'))Schema::create('ccs_facebook_sync',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->string('page_id',80);$t->string('page_name',180)->default('');$t->string('api_version',15);$t->text('token_encrypted');$t->boolean('auto_sync')->default(false);$t->boolean('publish_new')->default(true);$t->boolean('include_reels')->default(true);$t->unsignedInteger('interval_minutes')->default(60);$t->unsignedInteger('revision')->default(1);$t->timestamp('verified_at')->nullable();$t->timestamp('last_run_at')->nullable();$t->timestamp('last_success_at')->nullable();$t->timestamp('last_scheduler_at')->nullable();$t->timestamp('next_run_at')->nullable();$t->string('last_status',30)->default('ready');$t->text('last_message')->nullable();$t->string('run_token',64)->nullable();$t->timestamp('lease_until')->nullable();$t->timestamps();});
  if(!Schema::hasTable('ccs_facebook_video_sources'))Schema::create('ccs_facebook_video_sources',function(Blueprint $t){$t->id();$t->string('page_id',80);$t->string('video_id',80);$t->unsignedBigInteger('content_id');$t->string('thumbnail_url',2048)->nullable();$t->timestamps();$t->unique(['page_id','video_id']);});
 }
 public function down(): void {Schema::dropIfExists('ccs_facebook_video_sources');Schema::dropIfExists('ccs_facebook_sync');}
};
