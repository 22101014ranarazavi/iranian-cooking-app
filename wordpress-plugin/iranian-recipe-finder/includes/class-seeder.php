<?php
if (!defined('ABSPATH')) exit;

/**
 * Seeds the مواد اولیه / دسته‌بندی غذا taxonomies and 10 starter
 * Iranian recipes on first activation, so the site isn't empty on
 * install. Only runs once — if any "recipe" post already exists
 * (including a trashed one), it does nothing.
 *
 * The ingredient catalog here uses small integer "catalog ids" purely
 * to wire each recipe to its ingredients while building the seed data;
 * they are translated to the real WordPress term ids created during
 * seeding and are not stored anywhere afterwards.
 */
final class IRCF_Seeder
{
    public static function seed_if_empty(): void
    {
        $existing = get_posts([
            'post_type' => 'recipe',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);
        if (!empty($existing)) {
            return;
        }

        $term_id_by_catalog_id = self::seed_ingredient_terms();
        $category_term_id_by_name = [];

        foreach (self::recipes() as $recipe) {
            $cat_name = $recipe['category'];
            if (!isset($category_term_id_by_name[$cat_name])) {
                $term = get_term_by('name', $cat_name, 'recipe_category')
                    ?: wp_insert_term($cat_name, 'recipe_category');
                $category_term_id_by_name[$cat_name] = is_wp_error($term) ? null : (is_array($term) ? $term['term_id'] : $term->term_id);
            }

            $post_id = wp_insert_post([
                'post_type' => 'recipe',
                'post_title' => $recipe['title'],
                'post_status' => 'publish',
                'post_content' => '',
            ]);
            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            if ($category_term_id_by_name[$cat_name]) {
                wp_set_object_terms($post_id, [(int)$category_term_id_by_name[$cat_name]], 'recipe_category');
            }

            $ingredient_term_ids = array_values(array_filter(array_map(
                static fn($cid) => $term_id_by_catalog_id[$cid] ?? null,
                $recipe['ingredient_ids']
            )));
            wp_set_object_terms($post_id, $ingredient_term_ids, 'ingredient');

            $lines = array_map(
                static fn($ing) => self::catalog_name($ing['id']) . ': ' . $ing['qty'],
                $recipe['ingredients']
            );

            update_post_meta($post_id, '_ircf_prep_minutes', $recipe['prep_minutes']);
            update_post_meta($post_id, '_ircf_cook_minutes', $recipe['cook_minutes']);
            update_post_meta($post_id, '_ircf_servings', $recipe['servings']);
            update_post_meta($post_id, '_ircf_difficulty', $recipe['difficulty']);
            update_post_meta($post_id, '_ircf_ingredient_lines', $lines);
            update_post_meta($post_id, '_ircf_steps', $recipe['steps']);
            update_post_meta($post_id, '_ircf_ingredient_ids', $ingredient_term_ids);
        }
    }

    /** @return array<int,int> catalog id -> real WP term id */
    private static function seed_ingredient_terms(): array
    {
        $map = [];
        foreach (self::ingredient_catalog() as $group => $items) {
            $parent = get_term_by('name', $group, 'ingredient')
                ?: wp_insert_term($group, 'ingredient');
            $parent_id = is_wp_error($parent) ? 0 : (is_array($parent) ? $parent['term_id'] : $parent->term_id);

            foreach ($items as $catalog_id => $name) {
                $term = get_term_by('name', $name, 'ingredient')
                    ?: wp_insert_term($name, 'ingredient', ['parent' => $parent_id]);
                if (!is_wp_error($term)) {
                    $map[$catalog_id] = is_array($term) ? $term['term_id'] : $term->term_id;
                }
            }
        }
        return $map;
    }

    private static function catalog_name(int $catalog_id): string
    {
        foreach (self::ingredient_catalog() as $items) {
            if (isset($items[$catalog_id])) {
                return $items[$catalog_id];
            }
        }
        return '؟';
    }

    private static function ingredient_catalog(): array
    {
        return [
            'لبنیات' => [1 => 'تخم‌مرغ', 2 => 'ماست', 3 => 'شیر', 4 => 'پنیر', 5 => 'کره'],
            'سبزیجات و سبزی‌خوردن' => [
                10 => 'پیاز', 11 => 'سیر', 12 => 'سیب‌زمینی', 13 => 'گوجه‌فرنگی',
                14 => 'بادمجان', 15 => 'اسفناج',
                16 => 'جعفری، گشنیز و شوید (سبزی کوکو)',
                17 => 'سبزی قورمه (شنبلیله، جعفری، گشنیز، تره)',
                18 => 'فلفل دلمه‌ای',
            ],
            'پروتئین' => [20 => 'مرغ', 21 => 'گوشت چرخ‌کرده', 22 => 'گوشت خورشتی (چانه/راسته)', 23 => 'جگر مرغ'],
            'غلات و حبوبات' => [
                30 => 'برنج', 31 => 'عدس', 32 => 'لوبیا قرمز', 33 => 'لوبیا چیتی',
                34 => 'لپه', 35 => 'نخود', 36 => 'نان خشک یا آرد نان',
            ],
            'ادویه، چاشنی و اساسی' => [
                40 => 'نمک و فلفل سیاه', 41 => 'زردچوبه', 42 => 'زعفران', 43 => 'رب گوجه‌فرنگی',
                44 => 'لیمو عمانی', 45 => 'آبلیمو', 46 => 'روغن', 47 => 'آرد', 48 => 'گردو',
            ],
        ];
    }

    private static function recipes(): array
    {
        // Same 10 starter recipes as the standalone demo
        // (demo/data/recipes.php) — kept in sync by hand since this
        // file must stay self-contained inside the plugin zip.
        return require __DIR__ . '/seed-recipes.php';
    }
}
