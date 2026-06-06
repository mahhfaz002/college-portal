# School Portal — School Management System

A multi-role school management system built with **Laravel 12 / PHP 8.2**. It is designed to be **re-skinned per school** from an in-app settings panel (name, logo, colours, currency, grading scheme, academic term/session) — no code changes needed to onboard a new school.

## Features

- **Role-based dashboards** — proprietor, principal, admin, ICT, accountant, exam officer, teacher, student. Each role lands on `/dashboard` and is routed to the right view automatically.
- **Students** — enrolment, profiles, ID cards, promotion between classes, report cards (PDF).
- **Academics** — subjects, score entry per class/subject, configurable grading scheme, report cards.
- **Finance** — fee balances, payments, printable receipts.
- **Attendance** — daily student attendance + reports.
- **Admissions** — public application form with document uploads, admin review/approve/reject.
- **Communications** — announcements targeted by audience (all / staff / a specific role).
- **Operations** — inventory, library (book catalogue + borrowing), transport (routes/vehicles), HR & payroll, alumni register, quizzes/exams.
- **School settings** — branding + academics editable in-app, with a configurable grading scheme.
- **Activity log** — auditable record of key actions (settings changes, admissions decisions, score entry).
- **Security** — auth with email verification, forced first-login password change, role middleware.

## Local setup

```bash
cd school-portal
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # creates schema + demo data
php artisan storage:link           # serve uploaded logos/documents
npm run build                      # or: npm run dev
php artisan serve
```

Visit http://127.0.0.1:8000.

### Seeded demo logins (password = `password` for all)

| Role         | Email                       |
|--------------|-----------------------------|
| Proprietor   | proprietor@mahhfaz.edu   |
| Principal    | principal@mahhfaz.edu    |
| Admin        | admin@mahhfaz.edu        |
| ICT          | ict@mahhfaz.edu          |
| Accountant   | bursar@mahhfaz.edu       |
| Exam Officer | exams@mahhfaz.edu        |
| Teacher      | teacher@mahhfaz.edu      |

## Customising for a new school

Log in as proprietor/admin → user menu → **School Settings**. From there you can set:

- School name, tagline, logo, primary colour, address, phone, public email
- Staff email domain (used when auto-generating teacher accounts)
- Currency symbol, current term, current session, max CA / exam scores
- The **grading scheme** (score band → grade → remark), which drives report cards everywhere via the `grade_for()` helper

Defaults live in `database/seeders/SettingsSeeder.php`. Settings are cached and read through the global `setting('key', $default)` helper and the `$school` array shared with every view.

## Testing

```bash
php artisan test
```

Covers: auth flows, every role dashboard, all module index pages, settings update, and the announcement lifecycle. See [docs/TEST_PLAN.md](docs/TEST_PLAN.md) for the manual QA checklist.

## Deployment

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for **Laravel Cloud** (recommended) step-by-step, plus notes on other hosts.
