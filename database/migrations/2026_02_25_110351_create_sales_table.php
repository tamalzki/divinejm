<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('customer_name'); // Store customer name instead of FK
            $table->string('dr_number'); // Delivery Receipt Number
            $table->date('sale_date');
            
            // Amounts
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            
            // Payment tracking
            $table->enum('payment_status', ['paid', 'to_be_collected', 'partial'])->default('to_be_collected');
            $table->enum('payment_mode', ['cash', 'gcash', 'cheque', 'bank_transfer', 'other'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->unique(['branch_id', 'customer_name', 'dr_number']);
            $table->index(['branch_id', 'sale_date']);
            $table->index('dr_number');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};