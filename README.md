# Supermarket Management System

Professional local supermarket management app built with Laravel, Blade, Livewire, Alpine.js, Tailwind CSS, and MySQL.

**Currency:** Afghan Afghani (AFN)  
**Auth:** Single admin account (email/password only — no Google / social login)  
**Mode:** Local / offline daily operations

## Environment

| Tool | Detected version |
|------|------------------|
| PHP | 8.2.x |
| Laravel | 12.x |
| Composer | 2.x |
| MySQL | 8.0 |
| Node.js | 22.x |

## Local setup

1. Copy environment file and set MySQL credentials:

```bash
cp .env.example .env
php artisan key:generate
```

2. Create MySQL database `inventory` (or update `DB_DATABASE` in `.env`).

3. Install dependencies (if needed):

```bash
composer install
npm install
```

4. Run migrations and seed the admin user:

```bash
php artisan migrate:fresh --seed
```

5. Start the app:

```bash
php artisan serve
npm run dev
```

Open: http://127.0.0.1:8000

### Default admin login

- **Email:** `admin@supermarket.local`
- **Password:** `password`

Change this password after first login.

## Development phases

1. **Phase 1 (done):** Foundation, auth cleanup, users table refresh, packages, admin seeder  
2. **Phase 2:** Categories, products, suppliers, customers, settings  
3. **Phase 3:** Purchases, batch inventory, stock movements  
4. **Phase 4:** POS, sales, payments, credit  
5. **Phase 5:** Expenses, cash register, returns  
6. **Phase 6:** Dashboard analytics, reports, receipts, backups  

## Database backup (local)

```bash
php artisan db:backup
```

*(Backup command will be added in a later phase.)*
