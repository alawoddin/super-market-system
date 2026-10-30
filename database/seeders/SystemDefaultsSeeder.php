<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Minimal system defaults only — no demo categories, products, customers, or suppliers.
 */
class SystemDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::current();

        foreach (['Rent', 'Electricity', 'Water', 'Internet', 'Salaries', 'Transportation', 'Maintenance', 'Other'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
