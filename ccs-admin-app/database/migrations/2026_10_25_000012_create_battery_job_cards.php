<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('battery_service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->uuid('request_token')->unique();
            $table->date('service_date');
            $table->unsignedInteger('mileage_km')->nullable();
            $table->unsignedSmallInteger('module_count');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id','id']);
        });
        Schema::create('battery_module_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battery_service_record_id')->constrained('battery_service_records')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('serial_number',100);
            $table->decimal('voltage',8,3)->nullable();
            $table->string('notes',250)->nullable();
            $table->unique(['battery_service_record_id','position']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('battery_module_readings');
        Schema::dropIfExists('battery_service_records');
    }
};
