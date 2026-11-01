<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['purchase_item_id']);
            $table->dropColumn('purchase_item_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['sale_date', 'customer_id']);
            $table->dropColumn('customer_id');
            $table->index('sale_date');
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id', 'payment_date']);
            $table->dropColumn('customer_id');
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['customer_debt_payments', 'supplier_payments']);
        });

        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Not reversible: the dropped data is gone. Restore a backup taken before this migration instead.
    }
};
