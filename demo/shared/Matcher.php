<?php
/**
 * Core ingredient-matching algorithm.
 *
 * Framework-agnostic on purpose: both the WordPress plugin and the
 * standalone PHP demo include this same file, so the matching logic
 * (and its behavior) is identical in both places.
 */

if (!class_exists('IRCF_Matcher')) {

final class IRCF_Matcher
{
    /**
     * How many missing ingredients still count as "almost ready".
     * Recipes missing more than this are left out of the results entirely.
     */
    const MAX_MISSING = 2;

    /**
     * @param array $recipes    List of recipes. Each recipe is an array with
     *                          at least: 'id', 'ingredient_ids' => int[].
     * @param int[] $selectedIds Ingredient ids the user says they have.
     *
     * @return array{
     *   ready: array,     // 0 missing - can be cooked right now
     *   almost: array,    // 1..MAX_MISSING missing
     * }
     *   Each entry is the original recipe plus:
     *     'matched_count', 'missing_count', 'missing_ids'
     */
    public static function match(array $recipes, array $selectedIds): array
    {
        $selected = array_fill_keys(array_map('intval', $selectedIds), true);

        $ready = [];
        $almost = [];

        foreach ($recipes as $recipe) {
            $required = array_map('intval', $recipe['ingredient_ids'] ?? []);
            if (empty($required)) {
                continue;
            }

            $missingIds = array_values(array_filter(
                $required,
                static fn($id) => !isset($selected[$id])
            ));

            $missingCount = count($missingIds);
            $matchedCount = count($required) - $missingCount;

            // Require at least one real match so an empty selection
            // doesn't flood the "almost" bucket with everything.
            if ($matchedCount === 0) {
                continue;
            }

            $entry = $recipe + [
                'matched_count' => $matchedCount,
                'missing_count' => $missingCount,
                'missing_ids'   => $missingIds,
            ];

            if ($missingCount === 0) {
                $ready[] = $entry;
            } elseif ($missingCount <= self::MAX_MISSING) {
                $almost[] = $entry;
            }
        }

        $byBestMatch = static function (array $a, array $b): int {
            if ($a['missing_count'] !== $b['missing_count']) {
                return $a['missing_count'] <=> $b['missing_count'];
            }
            return $b['matched_count'] <=> $a['matched_count'];
        };

        usort($ready, $byBestMatch);
        usort($almost, $byBestMatch);

        return ['ready' => $ready, 'almost' => $almost];
    }
}

}
