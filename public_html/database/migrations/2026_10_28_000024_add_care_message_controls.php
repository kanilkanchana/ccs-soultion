<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // Automatic messages retain unique audit IDs; manual/serial messages use their own unique keys.
        Schema::table('ccs_care_outbox',function(Blueprint $t){$t->unsignedBigInteger('audit_id')->nullable()->change();});
        if(!Schema::hasTable('ccs_care_manual_requests'))Schema::create('ccs_care_manual_requests',function(Blueprint $t){$t->uuid('id')->primary();$t->unsignedBigInteger('user_id');$t->string('kind',20);$t->unsignedBigInteger('entity_id');$t->unsignedBigInteger('outbox_id')->nullable();$t->timestamps();});
        if(!Schema::hasTable('ccs_care_auto_serial_keys'))Schema::create('ccs_care_auto_serial_keys',function(Blueprint $t){$t->unsignedBigInteger('warranty_id')->primary();$t->unsignedBigInteger('audit_id');$t->unsignedBigInteger('outbox_id');$t->timestamps();});
        if(!\Illuminate\Support\Facades\DB::table('ccs_care_state')->where('key','developer_features_v3')->exists()){
            // Upgrade never silently starts paid/API traffic or direct private-data access.
            app(\App\Services\CcsCareFeatures::class)->save(['manual_enabled'=>true]);
        }
    }
    public function down(): void {Schema::dropIfExists('ccs_care_auto_serial_keys');Schema::dropIfExists('ccs_care_manual_requests');}
};
