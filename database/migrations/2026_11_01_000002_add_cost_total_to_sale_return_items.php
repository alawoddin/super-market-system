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
        DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->where('sale_returns.restock', true)
            ->where('sale_items.quantity', '>', 0)
            ->update(['sale_return_items.cost_total' => DB::raw('ROUND(sale_items.cost_total / sale_items.quantity * sale_return_items.quantity, 2)')]);
    }

    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('cost_total');
        });
    }
};
