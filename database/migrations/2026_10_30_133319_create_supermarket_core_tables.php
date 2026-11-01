<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('My Supermarket');
            $table->string('logo')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 10)->default('AFN');
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->unsignedInteger('low_stock_alert')->default(5);
            $table->unsignedInteger('expiration_alert_days')->default(7);
            $table->string('receipt_width', 10)->default('80mm');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->unique();
            $table->string('sku')->nullable()->unique();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('unit', 30)->default('piece');
            $table->decimal('min_stock', 15, 3)->default(5);
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_expiry')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'is_active']);
            $table->index('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('settings');
    }
};
