<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Brings back customers and credit (pay later) sales, removed on 2026-11-01
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('invoice_number')->constrained()->nullOnDelete();
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'payment_date']);
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('customer_debt_payments', 15, 2)->default(0)->after('cash_sales');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', fn (Blueprint $table) => $table->dropColumn('customer_debt_payments'));

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id', 'payment_date']);
            $table->dropColumn('customer_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
