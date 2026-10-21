<?php
if (!defined('ABSPATH')) exit;

/**
 * POST /wp-json/ircf/v1/match  { ingredients: number[] }
 * -> { ready: [...], almost: [...] }
 *
 * Mirrors demo/api/match.php exactly (same Matcher class), so the
 * algorithm behaves identically in the demo and on the real site.
 */
final class IRCF_REST
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route('ircf/v1', '/match', [
            'methods' => 'POST',
            'callback' => [self::class, 'match'],
            'permission_callback' => '__return_true',
            'args' => [
                'ingredients' => ['required' => true],
            ],
        ]);
    }

    public static function match(WP_REST_Request $request): WP_REST_Response
    {
        $selected = array_map('intval', (array)$request->get_param('ingredients'));

        $query = new WP_Query([
            'post_type' => 'recipe',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ]);

        $recipes = [];
        foreach ($query->posts as $post) {
            $ids = get_post_meta($post->ID, '_ircf_ingredient_ids', true);
            if (empty($ids)) {
                continue;
            }
            $recipes[] = [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'permalink' => get_permalink($post),
                'thumbnail' => get_the_post_thumbnail_url($post, 'medium') ?: null,
                'prep_minutes' => (int)get_post_meta($post->ID, '_ircf_prep_minutes', true),
                'cook_minutes' => (int)get_post_meta($post->ID, '_ircf_cook_minutes', true),
                'servings' => (int)get_post_meta($post->ID, '_ircf_servings', true),
                'ingredient_ids' => array_map('intval', (array)$ids),
            ];
        }

        $result = IRCF_Matcher::match($recipes, $selected);

        $names_by_id = [];
        foreach (get_terms(['taxonomy' => 'ingredient', 'hide_empty' => false]) as $term) {
            $names_by_id[$term->term_id] = $term->name;
        }

        $decorate = static function (array $entries) use ($names_by_id): array {
            return array_map(static function ($r) use ($names_by_id) {
                unset($r['ingredient_ids']);
                $r['missing_names'] = array_map(
                    static fn($id) => $names_by_id[$id] ?? '؟',
                    $r['missing_ids']
                );
                unset($r['missing_ids']);
                return $r;
            }, $entries);
        };

        return new WP_REST_Response([
            'ready' => $decorate($result['ready']),
            'almost' => $decorate($result['almost']),
        ]);
    }
}
