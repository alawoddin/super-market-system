<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('shop_name', 'My Supermarket')->update(['shop_name' => 'غزنویان هایپر']);
    }

    public function down(): void
    {
        DB::table('settings')->where('shop_name', 'غزنویان هایپر')->update(['shop_name' => 'My Supermarket']);
    }
};
