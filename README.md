# ForgeOps — Industrial Inventory & Release Management

ForgeOps is a Laravel 13, Blade, and MariaDB application for controlled industrial inventory and release workflows. It runs on conventional shared hosting without a Node.js process, Redis, Supervisor, Docker, or another always-on service.

## Included

- Login/logout with throttling, active-account enforcement, and hashed passwords.
- Granular database-backed roles/permissions: Administrator, Manager, and Inventory Operator.
- Product master data, locations, per-location stock, complete stock movement ledger, and row-locked transactions that prevent negative available stock.
- Release records, components, a constrained workflow (`DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → RELEASED → CLOSED`), approvals, rejections, and a timeline.
- Centralized database audit/activity logging with before/after data, IP/user-agent, route, entity, and result.
- Role-aware dashboard, activity center, CSV export, private attachment storage, responsive UI, validation, and demo data.

## Requirements

PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `xml`, and `zip`; Composer 2.6+; and MariaDB 10.6+/MySQL 8. The specified MariaDB 11.4 is supported.

## Setup

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
# configure DB_* in .env
php artisan migrate --seed
php artisan serve
```

Demo accounts (replace or remove after first sign-in):

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@forgeops.test` | `Password123!Ab` |
| Manager | `manager@forgeops.test` | `Password123!Ab` |
| Inventory Operator | `inventory@forgeops.test` | `Password123!Ab` |

## cPanel deployment

1. Create a MariaDB database/user in **MySQL Databases**, grant all privileges, and record the cPanel-prefixed credentials.
2. Run `composer install --no-dev --optimize-autoloader` locally (or through cPanel Terminal), then upload the application including `vendor/`, excluding `.env`.
3. Copy `.env.example` to `.env`, set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, HTTPS `APP_URL`, and `DB_*`. Keep `QUEUE_CONNECTION=sync`.
4. Point the domain/subdomain document root to the application’s `public/` directory. If that is impossible, copy only public assets to `public_html` and adjust `public/index.php` paths.
5. Give the web-server account write access to `storage/` and `bootstrap/cache/` (usually 775). Private uploads remain under `storage/app/private`.
6. Run `php artisan migrate --force --seed`. Without SSH, use cPanel’s Laravel application feature or an access-controlled temporary deployment script, then delete that script.
7. Configure SMTP if desired. Core app features remain available without email.
8. Enable AutoSSL/Let’s Encrypt; set `SESSION_SECURE_COOKIE=true` after HTTPS is live. Run `php artisan optimize` following deployment.

No scheduler is required today. If future functionality adds scheduled work, use cPanel Cron:

```bash
* * * * * /usr/local/bin/php /home/CPANEL_USER/forgeops/artisan schedule:run >> /dev/null 2>&1
```

## Verification and security

Run `php artisan test` and `php artisan route:list`. Tests cover authorization, dashboard access, stock-in, prevention of negative stock, and release rejection validation.

Permissions are checked server-side. Inventory and workflow changes use database transactions. Audit records have no edit/delete application route. Attachments are MIME/size allowlisted and stored outside public URLs. For production, preserve `APP_DEBUG=false`, unique secrets, HTTPS, strong database credentials, and least privilege.
