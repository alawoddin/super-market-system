<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            // Cost taken back out of COGS (0 when the item was not put back in stock)
            $table->decimal('cost_total', 15, 2)->default(0)->after('subtotal');
        });

        // Fill in returns made before this column existed
        DB::statement('
            UPDATE sale_return_items
            SET cost_total = COALESCE((
                SELECT ROUND(sale_items.cost_total / sale_items.quantity * sale_return_items.quantity, 2)
                FROM sale_items
                WHERE sale_items.id = sale_return_items.sale_item_id AND sale_items.quantity > 0
            ), 0)
            WHERE sale_return_id IN (SELECT id FROM sale_returns WHERE restock = 1)
        ');
    }

    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('cost_total');
        });
    }
};
