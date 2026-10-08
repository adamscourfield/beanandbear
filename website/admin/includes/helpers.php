<?php
declare(strict_types=1);

function slugify(string $title): string
{
    $s = strtolower($title);
    $s = str_replace(['&', ','], ' ', $s);
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/\s+/', '-', trim($s));
    $s = preg_replace('/-+/', '-', $s);
    return $s;
}

/**
 * Parse the ingredients textarea format into rows.
 * A line starting with "## " introduces a group heading.
 * Other lines are "qty | item" (qty may be blank, e.g. "| Zest of 1 lemon").
 */
function parse_ingredients_text(string $text): array
{
    $rows = [];
    $group = null;
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (str_starts_with($line, '##')) {
            $group = trim(substr($line, 2));
            continue;
        }
        if (str_contains($line, '|')) {
            [$qty, $item] = array_map('trim', explode('|', $line, 2));
        } else {
            $qty = '';
            $item = $line;
        }
        if ($item === '') {
            continue;
        }
        $rows[] = ['group' => $group, 'qty' => $qty, 'item' => $item];
    }
    return $rows;
}

function ingredients_to_text(array $rows): string
{
    $lines = [];
    $group = '__unset__';
    foreach ($rows as $row) {
        if ($row['group'] !== $group) {
            $group = $row['group'];
            if ($group !== null) {
                $lines[] = '## ' . $group;
            }
        }
        $qty = $row['qty'];
        $lines[] = ($qty !== '' ? $qty . ' | ' : '| ') . $row['item'];
    }
    return implode("\n", $lines);
}

function parse_steps_text(string $text): array
{
    $steps = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $steps[] = $line;
        }
    }
    return $steps;
}

function steps_to_text(array $steps): string
{
    return implode("\n", $steps);
}

function money(float $n): string
{
    return '£' . number_format($n, 2);
}

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES);
}
