# Back of House (staff admin area)

A login-gated area at `/admin/` for the stuff that shouldn't be public: recipes, staff time logs, ingredient inventory, and financials. Plain PHP + SQLite — no build step, no extra services, deploys as part of the same site folder you already upload to Hostinger.

## What's actually built

- **Login**: usernames/passwords in a small SQLite database (`data/admin.sqlite`, created automatically, never committed to git), passwords hashed with `password_hash()` (bcrypt), PHP sessions, a CSRF token on every form, and a lockout after 5 failed attempts (5 minutes) per account.
- **Recipes**: full CRUD, seeded with all 42 real flavours from `BeanAndBear_RecipeBook.pdf` (Edition 5) — name, tagline, story, "the point of this flavour," ingredients (with optional named sub-groups, e.g. Chocolate Brownie's base + brownie pieces), and numbered method steps. Each recipe's detail page shows the real flavour photo from `../assets/img/flavours/`, matched by a `flavour_key` slug. Ingredients and method are edited as plain-text (`qty | ingredient` per line, `## Group Name` to start a sub-group) rather than a dynamic multi-row form — simpler to build correctly and perfectly fine for the one or two people who'll use this.
- **Inventory**: ingredient list seeded with the 125 distinct ingredients used across those 42 recipes (stock starts at 0 — there's no real stock data to seed from). Stock changes go through an adjust screen that records an auditable `stock_movements` entry rather than letting the number be edited directly. Ingredients at or below their reorder level are flagged.
- **Time logs**: staff (name, role, active) and shifts (date, clock in/out, break, notes) with hours computed automatically. No seed data — there's no staff yet — but it's a real, working feature.
- **Financials**: weekly entries (consumer sales, direct COGS, other costs) with VAT, net sales, COGS%, an estimated card-fee line, and operating surplus computed the same way the business plan computes them, then measured against the plan's own £92,000/year threshold (the £22k illustrative debt service plus the £70k owner-income objective, ≈£1,769/week).

## What's deliberately not built

No 2FA, no password reset flow, no per-role permissions (everyone who signs in sees everything), no UI for adding a second staff account, no audit log beyond the stock-movement history. This is a minimal, real lock on the door and a genuinely useful set of tools behind it — reasonable for a small, founder-run shop today, worth outgrowing as the business and the team around it grow.

## First-time setup

1. Deploy the whole `website/` folder (including `admin/`) to Hostinger as usual.
2. Visit `https://beanandbear.uk/admin/` — it'll redirect you to `/admin/setup.php`.
3. Create the first account there. **This page only works once** — as soon as one account exists, it locks itself and just points to the login page.
4. From then on, `/admin/` → `/admin/login.php` → `/admin/dashboard.php`. The 42 recipes and 125 ingredients seed themselves automatically the first time the database is created.

To add a second staff account later (there's no UI for it yet), insert a row into the `users` table in `data/admin.sqlite` directly, with the password run through PHP's `password_hash($password, PASSWORD_DEFAULT)` — or ask for a small "add user" script when you're ready for it.

## Hosting requirements

- PHP with the `pdo_sqlite` extension (on by default on effectively all Hostinger PHP plans).
- The `data/` directory must be writable by PHP so it can create `admin.sqlite` on first run.
- `data/` and `includes/` each carry a `.htaccess` denying all direct web access — after deploying, it's worth confirming `https://beanandbear.uk/admin/data/admin.sqlite` actually 403s rather than downloading.

## Where things live

- `includes/db.php` — the schema (`recipes`, `recipe_ingredients`, `recipe_steps`, `ingredients`, `stock_movements`, `staff`, `time_entries`, `financial_weeks`, `users`) and the one-time seeding from `includes/seed_recipes.php` / `includes/seed_ingredients.php`.
- `includes/helpers.php` — slugify, the ingredients/method text-format parsers, money/HTML-escaping helpers.
- Each feature is `<name>.php` (list/detail), `<name>_edit.php` (add and edit in one form), `<name>_delete.php` (a confirmation page, since deletes are real and permanent).
