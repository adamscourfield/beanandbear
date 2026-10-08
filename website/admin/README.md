# Back of House (staff admin area)

A login-gated area at `/admin/` for the stuff that shouldn't be public: recipes, staff time logs, ingredient inventory, and financials. Plain PHP + SQLite — no build step, no extra services, deploys as part of the same site folder you already upload to Hostinger.

## What's actually built

- A real login: usernames/passwords in a small SQLite database (`data/admin.sqlite`, created automatically, never committed to git), passwords hashed with `password_hash()` (bcrypt), PHP sessions, a CSRF token on every form, and a lockout after 5 failed attempts (5 minutes) per account.
- A dashboard with four tiles: Recipes, Time logs, Inventory, Financials.
- Each of those four pages is a locked-down placeholder — a heading and an empty state. No data model yet; that's the next piece of work, by design.

## What's deliberately not built

No 2FA, no password reset flow, no audit log, no per-role permissions (everyone who signs in sees everything), no UI for adding a second account. This is a minimal, real lock on the door — reasonable for a small internal tool today, worth outgrowing once financials and staff data actually live behind it.

## First-time setup

1. Deploy the whole `website/` folder (including `admin/`) to Hostinger as usual.
2. Visit `https://beanandbear.uk/admin/` — it'll redirect you to `/admin/setup.php`.
3. Create the first account there. **This page only works once** — as soon as one account exists, it locks itself and just points to the login page.
4. From then on, `/admin/` → `/admin/login.php` → `/admin/dashboard.php`.

To add a second staff account later (there's no UI for it yet), insert a row into the `users` table in `data/admin.sqlite` directly, with the password run through PHP's `password_hash($password, PASSWORD_DEFAULT)` — or ask for a small "add user" script when you're ready for it.

## Hosting requirements

- PHP with the `pdo_sqlite` extension (on by default on effectively all Hostinger PHP plans).
- The `data/` directory must be writable by PHP so it can create `admin.sqlite` on first run.
- `data/` and `includes/` each carry a `.htaccess` denying all direct web access — after deploying, it's worth confirming `https://beanandbear.uk/admin/data/admin.sqlite` actually 403s rather than downloading.

## Extending a placeholder page

Each of `recipes.php`, `time-logs.php`, `inventory.php`, and `financials.php` already does the only two things that matter — `require_login()` and the shared page chrome (`includes/layout_top.php` / `includes/layout_bottom.php`) — so building one out is just replacing the `<div class="empty">` with a real table/form and adding whatever table(s) it needs to `includes/db.php`.
