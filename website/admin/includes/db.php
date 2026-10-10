<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $path = __DIR__ . '/../data/admin.sqlite';
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            failed_attempts INTEGER NOT NULL DEFAULT 0,
            locked_until TEXT
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            flavour_key TEXT UNIQUE NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            name TEXT NOT NULL,
            tagline TEXT NOT NULL DEFAULT \'\',
            story TEXT NOT NULL DEFAULT \'\',
            brief TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS recipe_ingredients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            group_name TEXT,
            qty TEXT NOT NULL DEFAULT \'\',
            item TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS recipe_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            step_number INTEGER NOT NULL,
            instruction TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ingredients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            unit TEXT NOT NULL DEFAULT \'\',
            current_stock REAL NOT NULL DEFAULT 0,
            reorder_level REAL,
            supplier TEXT NOT NULL DEFAULT \'\',
            cost_per_unit REAL,
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS stock_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ingredient_id INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
            change_amount REAL NOT NULL,
            reason TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS staff (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT \'\',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS time_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE CASCADE,
            work_date TEXT NOT NULL,
            clock_in TEXT NOT NULL,
            clock_out TEXT NOT NULL,
            break_minutes INTEGER NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS financial_weeks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            week_start TEXT UNIQUE NOT NULL,
            consumer_sales REAL NOT NULL DEFAULT 0,
            direct_cogs REAL NOT NULL DEFAULT 0,
            other_costs REAL NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        seed_if_empty($pdo);
    }
    return $pdo;
}

function seed_if_empty(PDO $pdo): void
{
    $recipeCount = (int) $pdo->query('SELECT COUNT(*) FROM recipes')->fetchColumn();
    if ($recipeCount === 0) {
        $recipes = require __DIR__ . '/seed_recipes.php';
        $insR = $pdo->prepare('INSERT INTO recipes (flavour_key, sort_order, name, tagline, story, brief) VALUES (?,?,?,?,?,?)');
        $insI = $pdo->prepare('INSERT INTO recipe_ingredients (recipe_id, group_name, qty, item, sort_order) VALUES (?,?,?,?,?)');
        $insS = $pdo->prepare('INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?,?,?)');
        foreach ($recipes as $r) {
            $insR->execute([$r['flavour_key'], $r['sort_order'], $r['name'], $r['tagline'], $r['story'], $r['brief']]);
            $rid = (int) $pdo->lastInsertId();
            foreach ($r['ingredients'] as $idx => $ing) {
                $insI->execute([$rid, $ing['group'], $ing['qty'], $ing['item'], $idx]);
            }
            foreach ($r['method'] as $idx => $step) {
                $insS->execute([$rid, $idx + 1, $step]);
            }
        }
    }

    $ingredientCount = (int) $pdo->query('SELECT COUNT(*) FROM ingredients')->fetchColumn();
    if ($ingredientCount === 0) {
        $names = require __DIR__ . '/seed_ingredients.php';
        $insIng = $pdo->prepare('INSERT OR IGNORE INTO ingredients (name) VALUES (?)');
        foreach ($names as $n) {
            $insIng->execute([$n]);
        }
    }
}
