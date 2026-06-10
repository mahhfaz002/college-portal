# Deploying to Laravel Cloud

This is the Laravel application root. Point the Laravel Cloud environment at the
`school-portal/` directory of the `mahhfaz002/college-portal` repo.

## 1. Build & deploy commands (Environment → Settings)

**Build command**
```
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

**Deploy command** (runs on every release)
```
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> First deploy only — seed the clean college + bootstrap logins:
> ```
> php artisan db:seed --force
> ```
> Remove it from the deploy command after the first successful release so it
> doesn't re-run. (Seeders are idempotent via `updateOrCreate`, but there's no
> reason to run them every deploy.)

## 2. Required environment variables

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://<your-app>.laravel.cloud` |
| `APP_KEY` | generate once: `php artisan key:generate --show` (Cloud can auto-set) |
| `DB_CONNECTION` | `mysql` (attach a Laravel Cloud managed MySQL database) |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `s3` |
| `DOCUMENTS_DISK` | `s3` — **important:** keeps uploaded documents across deploys |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_DEFAULT_REGION` / `AWS_BUCKET` | from your Laravel Cloud object storage / S3 bucket |

### Paystack (when keys are ready)
| Key | Value |
|-----|-------|
| `PAYSTACK_PUBLIC_KEY` | live/test public key |
| `PAYSTACK_SECRET_KEY` | live/test secret key |

Without a secret key in production, online payments are **disabled** (the
sandbox auto-confirm only runs outside production). Per-college keys set on the
College record override these platform defaults.

## 3. After the first deploy

- Log in as the bootstrap accounts (password `password`) and change them:
  `proprietor@mahhfaz.edu.ng`, `registrar@mahhfaz.edu.ng`, `ict@mahhfaz.edu.ng`.
- The Registrar creates all real staff (bursar, HODs, lecturers, librarian,
  student affairs, office secretary, exams officer) in-app.
- Student & applicant accounts are created automatically through the admission
  workflow.

## Notes

- Passport photos are stored as base64 in the database (deploy-safe).
- All other uploaded documents use `DOCUMENTS_DISK` — set it to `s3` in
  production or they will be lost when the container is recycled.
