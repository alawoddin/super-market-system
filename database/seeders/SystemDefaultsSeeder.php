<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Minimal system defaults only — no demo products.
 */
class SystemDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::current();
    }
}
