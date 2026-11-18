<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {if(!Schema::hasTable('ccs_website_content'))Schema::create('ccs_website_content',function(Blueprint $t){$t->id();$t->string('kind',20);$t->string('slug',100)->unique();$t->string('title',180);$t->boolean('active')->default(true);$t->integer('sort_order')->default(0);$t->text('data');$t->unsignedInteger('revision')->default(1);$t->unsignedBigInteger('updated_by')->nullable();$t->timestamps();$t->index(['kind','active','sort_order']);});}
 public function down(): void {Schema::dropIfExists('ccs_website_content');}
};
