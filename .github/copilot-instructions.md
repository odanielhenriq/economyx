<!-- .github/copilot-instructions.md -->
# Copilot / AI Agent Instructions for economyx

Brief actionable guidance so an AI coding agent becomes productive quickly in this Laravel repo.

- Purpose: This is a Laravel 12 application (see `composer.json`) with a small frontend via Vite (`package.json`, `vite.config.js`). Work spans backend Eloquent models, migrations, seeders, and light frontend assets in `resources/`.

- Quick start (developer commands):
  - Install PHP deps: `composer install`
  - Install JS deps: `npm install`
  - Create .env (if missing): copy `.env.example` -> `.env` and run `php artisan key:generate`
  - DB (local): the project uses a SQLite helper; `database/database.sqlite` is prepared in composer scripts — run migrations: `php artisan migrate --seed`
  - Run app locally: `php artisan serve` and frontend with `npm run dev` (or combined via `composer run-script dev`)
  - Run tests: `php artisan test` (project uses PHPUnit config at `phpunit.xml`)

- Project architecture highlights (what to read first):
  - `routes/web.php` and `routes/api.php` — entry points for routing and examples (API uses `auth:sanctum` middleware)
  - `app/Models/` — domain models (e.g. `Transaction.php`, `User.php`, `Category.php`, `Type.php`, `PaymentMethod.php`). Look at `$fillable`, `$casts`, and relationships.
  - `database/migrations/` — schema naming uses timestamps (typical Laravel). Example: `2025_12_01_214459_create_transactions_table.php`.
  - `database/seeders/` and `database/factories/` — seeding examples and test data. See `TransactionsTableSeeder.php` for usage of pivot `transaction_user` and attaching users.
  - `app/Http/Controllers/` — controllers live here (currently minimal; controllers extend `Controller.php`).
  - Frontend: `resources/js/`, `resources/css/`, and `vite.config.js` + `package.json` for build/dev commands.

- Important repo-specific patterns and conventions:
  - Eloquent relationships: Many-to-many between `User` and `Transaction` via pivot table `transaction_user`. Seeders attach users explicitly (see `TransactionsTableSeeder.php`).
  - Models use `$fillable` and `$casts` (eg `transaction_date => 'date', amount => 'decimal:2'`) — preserve casts when editing models or creating factories.
  - Seeders reference specific user emails when attaching (`User::where('email', '...')`) — avoid changing seed email strings unless updating seed expectations.
  - Composer scripts provide higher-level developer flows: `setup`, `dev`, `test`. Prefer using them for environment bootstrapping when available.

- Integration points / external dependencies to note:
  - Laravel Sanctum is installed (`laravel/sanctum`) — API auth uses `auth:sanctum` middleware in `routes/api.php`.
  - Vite + Tailwind for frontend bundling (`vite`, `tailwindcss`, `laravel-vite-plugin`) — asset building lives in `resources/`.
  - PHPUnit for tests (`phpunit/phpunit`) invoked via `php artisan test`.

- When making changes, check these files to avoid breaking conventions:
  - `composer.json` (scripts & PHP requirements)
  - `package.json` and `vite.config.js` (frontend build flow)
  - `database/` (migrations/seeders/factories) — seeders assume data shapes and user emails
  - `app/Models/*.php` (fillable/casts/relationship signatures)
  - `routes/*.php` (ensure middleware and route names align)

- Example actionable tasks and how to approach them:
  - Add a new API endpoint: add route to `routes/api.php`, implement controller in `app/Http/Controllers/`, create request validation if needed, and write a small feature test under `tests/Feature/`.
  - Modify transaction seed data: update `database/seeders/TransactionsTableSeeder.php` and run `php artisan db:seed --class=TransactionsTableSeeder`.

- Things NOT to assume:
  - There is no heavy controller layer yet — some business logic may appear in models/seeders.
  - The repo uses Laravel defaults; do not remove existing casts, fillable attributes, or seed email literals without running migrations & seeders locally first.

If anything in this doc is unclear or you'd like more examples (routes, seeders, or a typical PR checklist for this repo), tell me which section to expand. After your feedback I will iterate.
