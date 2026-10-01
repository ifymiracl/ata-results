# ATA Results (Laravel)

Multi-school result management, rebuilt from the WordPress plugin + theme on Laravel 13, with the same oxblood/gold/cream design.

## Run it

```bash
composer install
cp .env.example .env && php artisan key:generate
# MySQL Workbench: run  CREATE DATABASE ata_results CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# then set DB_USERNAME / DB_PASSWORD in .env  (see "Using MySQL" below)
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Demo (all PINs `123456`): school at `/greenfield` — staff `ADM-0001` (admin), `PRN-0001` (principal), `FRM-0001` (form teacher, JSS 1), `TCH-0001` / `TCH-0002` (subject teachers); students `GRE/26/0001…`; parents sign in with guardian phone `08030000001…`.
Platform console: `/platform` — `admin@ata.test` / `password` (change it!).

Tests: `php artisan test`.

## What carried over from the plugin
Schools with their own slug (old slugs redirect), staff roles (admin / principal / form teacher / subject teacher), PIN login for staff, students and parents (rate-limited, forced PIN change, emailed temporary PINs), classes, subjects, departments with electives, per-class subject plans, teaching assignments, score entry with locking, the **draft → submitted → reviewed → approved → published** chain, report cards with verification codes, annual averages / ranks / promotion with arm streaming, fees + Paystack, platform subscription billing (per active student), school registration requests and the platform admin console, CSV import for staff and students, ID cards.

## New in this rebuild
- **Attendance** per class per day (form teacher / admin), with term-to-date rate; visible to parents and on report cards.
- **Notices** targeted by audience (all/staff/students/parents) and class, pinnable.
- **Conduct & skills ratings** on report cards (1–5), filled by the form teacher.
- **Per-student teacher and principal comments** in the review step, plus class notes.
- **Class analytics**: grade distribution, subject averages / pass rate, top performers, students below benchmark.
- **Report cards**: class average and highest per subject, class position, QR code, printable single or whole-class batch (browser print → PDF), public verification at `/verify`.
- **Configurable score weights** (CA1/CA2/Exam) and grading scale per school.
- **Bulk score upload** via CSV template per sheet; results CSV export; students CSV export.
- **Apply promotion** — actually moves promoted students, marks graduates; subject-best awards alongside overall-best.
- **Manual fee payments** (cash/transfer) with receipts, partial payments, outstanding totals.
- **Audit log** per school and platform-wide.
- **Student/parent progress chart** across terms, dark mode, mobile-friendly layout.
- Paystack **webhook** (`/webhooks/paystack`, signature-verified) for both fees and subscriptions; secrets stored encrypted.

## Notes
- Staff / student / parent sessions live in the Laravel session (not WP users); the platform admin uses the standard `users` table (`is_platform_admin`).
- Mail uses Laravel's mailer (`MAIL_MAILER=log` by default — configure SMTP to actually send PIN emails).

## Using MySQL (MySQL Workbench)
1. In Workbench, connect to your server and run: `CREATE DATABASE ata_results CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. In `.env` set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=ata_results`, `DB_USERNAME`, `DB_PASSWORD`.
3. `php artisan migrate --seed` creates all tables; refresh the schema in Workbench to browse them.
(To use SQLite instead: `DB_CONNECTION=sqlite` and `touch database/database.sqlite`.)
