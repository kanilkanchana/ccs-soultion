<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->string('job_category', 40)->nullable();
            $table->string('service_type', 120)->nullable();
            $table->text('fault_codes')->nullable();
            $table->string('technician_name', 150)->nullable();
            $table->text('parts_notes')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('next_service_km')->nullable();
            $table->text('warranty_notes')->nullable();
        });
    }
    public function down(): void {
        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->dropColumn(['job_category','service_type','fault_codes','technician_name','parts_notes','estimated_minutes','next_service_date','next_service_km','warranty_notes']);
        });
    }
};
