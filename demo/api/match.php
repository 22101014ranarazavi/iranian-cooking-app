<?php
/**
 * POST { ingredients: number[] } -> JSON { ready: [...], almost: [...] }
 *
 * This is the same shape of endpoint the WordPress plugin exposes at
 * /wp-json/ircf/v1/match — see wordpress-plugin/.../class-rest.php.
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../shared/Matcher.php';

$ingredientsCatalog = require __DIR__ . '/../data/ingredients.php';
$recipes = require __DIR__ . '/../data/recipes.php';

$namesById = [];
foreach ($ingredientsCatalog as $group => $items) {
    foreach ($items as $id => $name) {
        $namesById[$id] = $name;
    }
}

$raw = json_decode(file_get_contents('php://input'), true);
$selected = array_map('intval', $raw['ingredients'] ?? []);

$result = IRCF_Matcher::match($recipes, $selected);

$decorate = static function (array $entries) use ($namesById): array {
    return array_map(static function ($r) use ($namesById) {
        return [
            'id' => $r['id'],
            'title' => $r['title'],
            'category' => $r['category'],
            'emoji' => $r['emoji'],
            'color' => $r['color'],
            'prep_minutes' => $r['prep_minutes'],
            'cook_minutes' => $r['cook_minutes'],
            'servings' => $r['servings'],
            'missing_count' => $r['missing_count'],
            'missing_names' => array_map(
                static fn($id) => $namesById[$id] ?? '؟',
                $r['missing_ids']
            ),
        ];
    }, $entries);
};

echo json_encode([
    'ready' => $decorate($result['ready']),
    'almost' => $decorate($result['almost']),
], JSON_UNESCAPED_UNICODE);
