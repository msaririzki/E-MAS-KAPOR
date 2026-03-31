# AGENTS.md

This guide is for coding agents and contributors working in this repository.
It prioritizes fast orientation, safe edits, and consistent Laravel conventions.

## 1) Project Snapshot

- App: **E-MAS-KAPOR** (Sistem Informasi Manajemen Kapor)
- Stack: Laravel 12, PHP 8.2+, Blade, Vite, Tailwind CSS v4, MySQL/SQLite
- Auth: Dual login via Gmail for admin roles and `nrp_nip` for `personil` (`AuthController`)
- RBAC: `spatie/laravel-permission` with roles `superadmin`, `admin`, `admin_gudang`, `admin_satker`, `personil`
- Data flow: master data -> user/personnel records -> kapor size collection -> kebutuhan satker -> budget planning -> warehouse/distribution/reporting

## 2) Repo Map (High Signal)

- `app/Http/Controllers/` - web controllers (auth, dashboard, admin CRUD, settings)
- `app/Http/Middleware/` - role/satker/system-lock request guards
- `app/Http/Requests/` - form validation request classes (currently partial adoption)
- `app/Models/` - Eloquent models + scopes/casts/relationships
- `app/Services/AuditLogger.php` - centralized audit logging helper
- `app/Imports/` + `app/Exports/` - Excel/PDF import/export logic
- `routes/web.php` - all application routes
- `resources/views/` - Blade pages for each role and module
- `database/migrations/` - schema evolution
- `database/seeders/` - baseline data + demo users and roles
- `tests/` - PHPUnit unit + feature tests (currently minimal)

## 3) Build, Lint, Test Commands

### Install & bootstrap

- `composer install`
- `npm install`
- `cp .env.example .env` (Windows: `copy .env.example .env`)
- `php artisan key:generate`
- `php artisan migrate --seed`

### Daily development

- `composer dev` - runs server + queue listener + Vite concurrently
- `php artisan serve` - backend only
- `npm run dev` - frontend assets watch mode
- `npm run build` - production asset build

### Lint/format

- `./vendor/bin/pint --test` - check PHP formatting without modifying files
- `./vendor/bin/pint` - apply Laravel Pint formatting
- JS linting is **not configured** (`eslint` script does not exist in `package.json`)

### Tests

- `composer test` - clears config and runs test suite
- `php artisan test` - run all tests
- `php artisan test --parallel` - parallel test run (if environment supports)

### Single test (important)

- By file: `php artisan test tests/Feature/ExampleTest.php`
- By class: `php artisan test --filter=Tests\\Feature\\ExampleTest`
- By method: `php artisan test --filter=test_the_application_returns_a_successful_response`
- File + method: `php artisan test tests/Feature/ExampleTest.php --filter=test_the_application_returns_a_successful_response`

### Useful maintenance

- `php artisan optimize:clear` - clear route/config/view caches
- `php artisan migrate:fresh --seed` - reset local DB with seed data (destructive)

## 4) Architecture & Domain Rules

- Roles gate most behavior; verify route middleware and role checks before changing access behavior.
- `admin_gudang` should remain constrained to warehouse/distribution flows even though some routes sit under the broader `/admin` prefix.
- `admin_satker` must remain satker-scoped (`satker.scope` middleware + model scopes).
- Superadmin settings control fiscal year and system lock state.
- Personnel size input currently centers on JSON `personnels.kapor_sizes` with supporting export/report logic.
- Legacy `KaporSubmission` references still exist in parts of the codebase; be careful to confirm which path is the active source of truth before editing reports/settings.
- Audit logs are first-class; destructive/admin actions should continue logging via `AuditLogger::log(...)`.

## 5) Code Style Guidelines (Observed + Expected)

### PHP & formatting

- Follow PSR-12 and Laravel style (`pint` is the formatter of record).
- 4 spaces, LF line endings, UTF-8 (`.editorconfig`).
- Prefer short arrays `[]`, trailing commas in multiline arrays, and explicit visibility.
- Keep methods focused; move heavy business rules to services/import/export classes when controller grows.

### Imports and namespaces

- Use grouped top-level `use` statements; avoid fully-qualified classes inline unless one-off.
- Keep imports ordered and remove unused imports after edits.
- Use proper namespace roots: `App\`, `Database\Seeders\`, `Tests\`.

### Types and signatures

- Add scalar/return types on methods whenever practical.
- Type-hint framework dependencies (`Request`, model route bindings, `Response` where relevant).
- For models, define casts in `casts()` for booleans, JSON arrays, datetimes.
- Prefer typed Form Requests over inline `$request->validate(...)` for complex validation paths.

### Naming conventions

- Classes: PascalCase (`PersonnelController`, `AuditLogger`).
- Methods/variables: camelCase (`storeMeasurements`, `$submittedCount`).
- DB columns/table names: snake_case/plural (`kapor_submissions`, `satker_id`).
- Route names use dot notation by domain (`admin.personnel.index`).

### Controller and request patterns

- Keep controllers HTTP-focused: validate -> delegate -> return response/redirect.
- Use route model binding when IDs represent model resources.
- Return user-facing messages via flash session (`with('success'/'error'/'warning', ...)`).
- For expensive imports, keep timeout handling and clear summary feedback.

### Data integrity and transactions

- Use transactions for multi-record writes (`DB::transaction` or begin/commit/rollback).
- Ensure User <-> Personnel lifecycle stays synchronized (create/update/delete paths).
- Preserve uniqueness constraints (`nrp_nip`, `nrp`) and foreign key integrity.

### Error handling

- Catch exceptions only when adding context/recovery; avoid swallowing failures silently.
- Prefer validation exceptions for request errors.
- For forbidden access/system lock, keep explicit `abort(403, ...)` behavior.
- Do not expose sensitive details in production-facing messages.

### Security and auth

- Never commit `.env` or secrets.
- Passwords must always be hashed (`Hash::make` or `hashed` cast workflows).
- Respect `is_active` checks in authentication/authorization-sensitive flows.

### Views and frontend

- Blade-first UI; keep logic in controllers/models, not inside view templates.
- Reuse existing layout patterns in `resources/views/layouts/app.blade.php`.
- Preserve role-based menu visibility and route availability consistency.

### Testing expectations for changes

- For backend logic changes, add/update a Feature test at minimum.
- For pure utility/service logic, add Unit tests when possible.
- Run targeted test(s) first, then `php artisan test` before finalizing major edits.

## 6) DB/Seed Notes for Agents

- Seed order matters: roles/permissions, ranks, satkers, kapor items, settings, demo users.
- Demo credentials are documented in `README.md`; use only for local/dev.
- Some data assumptions exist (e.g., satker codes like `POLDA-NTB`, rank names) in seeders/imports.

## 7) Cursor/Copilot Rules Status

- `.cursorrules`: not found
- `.cursor/rules/`: not found
- `.github/copilot-instructions.md`: not found

No external agent rules are currently enforced beyond this AGENTS.md.

## 8) Laravel Boost (Optional)

If the team wants tighter AI-assisted workflows in Laravel context:

- Install (dev): `composer require --dev laravel/boost`
- Review docs: https://laravel.com/docs/boost
- Add team-specific conventions into this `AGENTS.md` after adopting Boost.

## 9) Safe Change Checklist

- Run formatter: `./vendor/bin/pint --test` (or apply fixes with `./vendor/bin/pint`)
- Run relevant test(s), especially single-file/method target for changed area
- Verify role/scope behavior for admin/admin_satker/personil impacted routes
- Ensure migrations/seed assumptions remain valid
- Update `README.md` and this file when workflow/architecture changes
