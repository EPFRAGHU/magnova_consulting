# Magnova Consulting — Manpower Billing & Payroll System

Multi-tenant Laravel + Inertia/React app for manpower/security service providers to manage clients, employees, attendance, wages, invoicing, payslips and EPF ECR filing. First tenant: **Magnova Consulting Pvt Ltd**.

## Stack

- Laravel 13, PHP 8.2+
- Inertia.js + React (TypeScript), shadcn/ui
- MySQL
- `stancl/tenancy` — single-database tenancy (shared MySQL DB, every business table row-scoped by `tenant_id` via the `BelongsToTenant` trait + global scope). Tenant is resolved from the authenticated user's `tenant_id` (see `App\Http\Middleware\IdentifyTenantFromUser`), not from a domain/subdomain.
- `barryvdh/laravel-dompdf` for payslip/invoice PDFs and Blade-based ECR text export

## Setup

1. Install PHP/Composer/Node dependencies:

```bash
composer install
npm install
```

2. Copy the environment file and set your MySQL credentials:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for your local MySQL server, then create the database:

```sql
CREATE DATABASE magnova CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Migrate and seed sample data (Magnova tenant, one owner login, 3 sample clients, 9 sample employees, a full month of attendance):

```bash
php artisan migrate --seed
```

4. Run the app:

```bash
php artisan serve
npm run dev
```

Visit `http://localhost:8000` and log in with the seeded owner account printed by the seeder (see `database/seeders/DatabaseSeeder.php` for the email; password is `password`).

## Typical workflow

1. **Clients** — add/verify client establishments.
2. **Employees** — add employees under each client with their rate/day and statutory IDs.
3. **Attendance** — mark daily attendance for a client/month.
4. **Wages** — review auto-computed working days/gross/EPF/ESIC, add bonus if any, save wages for the month.
5. **Invoices** — generate the monthly client invoice from saved wages, download the tax invoice PDF.
6. **Payslips** — download individual or bulk (one combined PDF) payslips for a client/month.
7. **ECR Export** — download the EPFO ECR `.txt` file for a tenant/month.

## Statutory rates

Fixed rates (EPF/ESIC employee & employer contributions, service charge, CGST/SGST) live in `config/magnova.php` — same across all clients/tenants for now.

## Deployment

Target: Render.com. Provide a MySQL database (Render MySQL or an external managed MySQL) and set the standard Laravel `.env` variables plus `APP_KEY`. Run `php artisan migrate --force` as a release step.
