<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('ccs_care_challenges', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('session_binding',64)->index(); $t->string('otp_hash')->nullable();
            $t->string('epoch'); $t->unsignedTinyInteger('attempts')->default(0);
            $t->string('state',20); $t->timestamp('expires_at'); $t->timestamps();
        });
        Schema::create('ccs_care_outbox', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('customer_id'); $t->unsignedBigInteger('audit_id')->unique();
            $t->string('kind',20); $t->text('parameters'); $t->string('state',20)->default('pending');
            $t->string('message_id',550)->nullable(); $t->string('error',500)->nullable(); $t->timestamps();
        });
        Schema::create('ccs_care_state', function (Blueprint $t) {
            $t->string('key')->primary(); $t->text('value'); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ccs_care_challenges'); Schema::dropIfExists('ccs_care_outbox'); Schema::dropIfExists('ccs_care_state');
    }
};
