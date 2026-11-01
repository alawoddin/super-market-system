# Supermarket Management System

Local supermarket management app for Afghanistan (AFN), built with Laravel 12, Blade, Livewire, Alpine.js, and MySQL.

## Features

- Single admin login (no Google / social auth)
- Dashboard with live stats & Chart.js
- Products (name, barcode, quantity, prices; Excel import/export)
- POS with USB barcode scanner support
- Cash-only payments (AFN)
- No card / online payment options
- Expenses & daily cash register
- Reports (sales, P&L with real COGS, expenses, inventory)
- Thermal receipt printing (58mm / 80mm)
- Local database backup / restore

## Requirements

- PHP 8.2+
- MySQL 8
- Composer
- Node.js (optional, for Vite assets; admin UI uses local Bootstrap theme)

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Configure MySQL in `.env` (`DB_DATABASE=inventory` by default), then:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000

### Admin login

- Email: `admin@supermarket.local`
- Password: `password`

Change the password after first login (Profile).

## Daily workflow

1. Open **Cash Register** with opening balance
2. Add **Products** (name, barcode, quantity, purchase and selling price)
3. Sell from **POS** (scan barcodes)
4. Close cash register at end of day
5. Create a **Backup** regularly

## Notes

- All money values are AFN
- Stock never goes negative; expired batches cannot be sold
- Profit uses batch purchase costs (COGS), not latest product price only
