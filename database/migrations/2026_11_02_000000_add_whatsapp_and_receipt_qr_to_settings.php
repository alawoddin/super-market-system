<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('whatsapp', 50)->nullable()->after('phone');
            $table->string('receipt_qr')->nullable()->after('logo');
        });

        DB::table('settings')->whereNull('whatsapp')->update(['whatsapp' => '0747333670']);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'receipt_qr']);
        });
    }
};
