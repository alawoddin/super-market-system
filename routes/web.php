<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('pos.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/admin/profile', [AdminController::class, 'AdminProfile'])->name('admin.profile');
    Route::post('/profile/store', [AdminController::class, 'ProfileStore'])->name('profile.store');
    Route::post('/admin/password/update', [AdminController::class, 'AdminPasswordUpdate'])->name('admin.password.update');
    Route::post('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');

    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('products/barcode/generate', [ProductController::class, 'generateBarcodeAjax'])->name('products.barcode.generate');
    Route::get('products-datatable', [ProductController::class, 'datatable'])->name('products.datatable');
    Route::resource('products', ProductController::class);

    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/lookup', [PosController::class, 'lookup'])->name('pos.lookup');
    Route::get('pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::resource('sales', SaleController::class)->only(['index', 'show', 'create', 'store']);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/batches', [InventoryController::class, 'batches'])->name('inventory.batches');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    Route::resource('expenses', ExpenseController::class);

    Route::get('cash-register', [CashRegisterController::class, 'index'])->name('cash-register.index');
    Route::post('cash-register/open', [CashRegisterController::class, 'open'])->name('cash-register.open');
    Route::post('cash-register/close', [CashRegisterController::class, 'close'])->name('cash-register.close');
    Route::get('cash-register/{cashRegister}', [CashRegisterController::class, 'show'])->name('cash-register.show');

    Route::resource('sale-returns', SaleReturnController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/cash-register', [ReportController::class, 'cashRegister'])->name('reports.cash-register');
    Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{file}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::post('backups/delete', [BackupController::class, 'destroy'])->name('backups.destroy');
});

require __DIR__.'/auth.php';
