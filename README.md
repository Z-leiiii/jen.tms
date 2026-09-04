# jen.tms

Task management system: a Laravel 12 API (`backend/`) and a Vue 3 + Vite SPA (`frontend/`).

## Requirements

- PHP 8.2+ with Composer
- Node.js 20+

## Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link   # local disk for task attachments
npm install && npm run build   # builds the Inertia web pages the starter routes render
php artisan serve              # http://localhost:8000
```

Tests: `php artisan test`. Code style: `vendor/bin/pint`.

Attachments use the disk named by `ATTACHMENT_DISK` (`public` locally). Set it to
`supabase` and fill in the `SUPABASE_STORAGE_*` variables to store files in Supabase.

## Frontend

```bash
cd frontend
npm install
cp .env.example .env   # VITE_API_BASE_URL, defaults to http://localhost:8000/api
npm run dev            # http://localhost:5173
```

`npm run build` produces the production bundle in `frontend/dist`.

## API

All routes are prefixed with `/api`. `/register`, `/login`, `/forgot-password` and
`/reset-password` are public; everything else requires a Sanctum bearer token sent as
`Authorization: Bearer <token>`. See `backend/routes/api.php` for the full list.
