# Bulloch Pricebook

Laravel 12 + Filament v3 admin application for managing the BT9000 price book.

- **URL:** https://pricebook.hellodeer.test
- **Admin login:** admin@example.com / 111111

---

## Requirements

- PHP 8.3+
- Composer
- MySQL
- [Laravel Valet](https://laravel.com/docs/valet)

---

## Setup

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set:

```dotenv
APP_URL=https://pricebook.hellodeer.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pricebook
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Create the MySQL database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS pricebook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Run migrations and seed the admin user

```bash
php artisan migrate --seed
```

This creates all tables and seeds the admin account:
- **Email:** admin@example.com
- **Password:** 111111

### 5. Set up Valet

From the project root:

```bash
valet link pricebook.hellodeer
valet secure pricebook.hellodeer
```

### 6. Open in browser

Visit [https://pricebook.hellodeer.test](https://pricebook.hellodeer.test) — it will redirect to the login page.

---

## Notes

- The `data/` folder contains the source BT9000 XML price book file. Do not delete it.
- All routes require authentication. Unauthenticated requests are redirected to `/admin/login`.
- The Filament admin panel is at `/admin`.
