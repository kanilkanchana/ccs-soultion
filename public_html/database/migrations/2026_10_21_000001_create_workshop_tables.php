<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Assumes Laravel's default users table already exists.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 32)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable()->index();
            $table->string('whatsapp', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('vehicle_code', 32)->unique();
            $table->string('registration_number', 30)->index();
            $table->string('make', 80);
            $table->string('model', 100);
            $table->string('generation', 40)->nullable();
            $table->unsignedSmallInteger('manufacture_year')->nullable();
            $table->string('colour', 40)->nullable();
            $table->unsignedInteger('current_mileage_km')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('repair_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number', 40)->unique();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('new_enquiry')->index();
            $table->text('complaint');
            $table->text('diagnosis')->nullable();
            $table->text('recommended_work')->nullable();
            $table->text('approved_work')->nullable();
            $table->unsignedInteger('mileage_at_service_km')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('warranty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 80);
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->enum('limit_basis', ['time_first','distance_first','whichever_first','custom'])->nullable();
            $table->text('terms')->nullable();
            $table->boolean('approved_by_owner')->default(false);
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->restrictOnDelete();
            $table->foreignId('warranty_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedInteger('mileage_limit_km')->nullable();
            $table->text('terms_snapshot')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->string('name');
            $table->string('condition', 30)->nullable();
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->decimal('selling_price_lkr', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('repair_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('quantity_change');
            $table->string('reason', 40);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('repair_job_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal_lkr', 12, 2)->default(0);
            $table->decimal('discount_lkr', 12, 2)->default(0);
            $table->decimal('tax_lkr', 12, 2)->default(0);
            $table->decimal('total_lkr', 12, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price_lkr', 12, 2);
            $table->decimal('line_total_lkr', 12, 2);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_lkr', 12, 2);
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['audit_logs','payments','invoice_items','invoices','stock_movements','inventory_items','warranties','warranty_rules','repair_jobs','vehicles','customers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
