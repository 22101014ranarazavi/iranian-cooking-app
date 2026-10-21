<?php
if (!defined('ABSPATH')) exit;

/**
 * [ircf_finder]  — the ingredient picker + "what can I cook" results.
 * [ircf_recipes] — browsable, searchable grid of all recipes.
 *
 * Drop either shortcode into any page (e.g. the homepage) with the
 * block editor's Shortcode block.
 */
final class IRCF_Shortcode
{
    public static function register(): void
    {
        add_shortcode('ircf_finder', [self::class, 'render_finder']);
        add_shortcode('ircf_recipes', [self::class, 'render_recipes']);
    }

    private static function enqueue_assets(): void
    {
        wp_enqueue_style('ircf-frontend', IRCF_URL . 'assets/css/frontend.css', [], IRCF_VERSION);
    }

    public static function render_finder(): string
    {
        self::enqueue_assets();
        wp_enqueue_script('ircf-frontend', IRCF_URL . 'assets/js/frontend.js', [], IRCF_VERSION, true);
        wp_localize_script('ircf-frontend', 'IRCF', [
            'restUrl' => esc_url_raw(rest_url('ircf/v1/match')),
            'aiRestUrl' => esc_url_raw(rest_url('ircf/v1/ai-suggest')),
        ]);

        $groups = get_terms([
            'taxonomy' => 'ingredient',
            'parent' => 0,
            'hide_empty' => false,
        ]);

        ob_start();
        ?>
        <div class="ircf-finder">
            <form id="ircf-picker-form" class="ircf-picker">
                <?php foreach ($groups as $group): ?>
                    <?php
                    $children = get_terms([
                        'taxonomy' => 'ingredient',
                        'parent' => $group->term_id,
                        'hide_empty' => false,
                    ]);
                    if (empty($children)) continue;
                    ?>
                    <div class="ircf-picker-group">
                        <h3><?= esc_html($group->name) ?></h3>
                        <div class="ircf-chip-grid">
                            <?php foreach ($children as $child): ?>
                                <div class="ircf-chip">
                                    <input type="checkbox" name="ingredients[]" value="<?= esc_attr($child->term_id) ?>" id="ircf-ing-<?= esc_attr($child->term_id) ?>">
                                    <label for="ircf-ing-<?= esc_attr($child->term_id) ?>"><?= esc_html($child->name) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="ircf-picker-actions">
                    <button type="submit" class="ircf-btn">🔍 پیشنهاد غذا بده</button>
                    <button type="button" class="ircf-btn ircf-btn-ghost" id="ircf-clear-btn">پاک کردن انتخاب‌ها</button>
                    <span class="ircf-selected-count" id="ircf-selected-count">چیزی انتخاب نشده</span>
                </div>
            </form>
            <div id="ircf-results"></div>
            <div id="ircf-ai-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_recipes(): string
    {
        self::enqueue_assets();

        $q = isset($_GET['ircf_q']) ? sanitize_text_field(wp_unslash($_GET['ircf_q'])) : '';
        $cat = isset($_GET['ircf_cat']) ? sanitize_text_field(wp_unslash($_GET['ircf_cat'])) : '';

        $args = [
            'post_type' => 'recipe',
            'post_status' => 'publish',
            'posts_per_page' => 24,
            's' => $q,
        ];
        if ($cat !== '') {
            $args['tax_query'] = [[
                'taxonomy' => 'recipe_category',
                'field' => 'slug',
                'terms' => $cat,
            ]];
        }

        $query = new WP_Query($args);
        $categories = get_terms(['taxonomy' => 'recipe_category', 'hide_empty' => false]);

        ob_start();
        ?>
        <div class="ircf-recipes">
            <form class="ircf-filters" method="get">
                <input type="text" name="ircf_q" placeholder="جست‌وجوی نام غذا…" value="<?= esc_attr($q) ?>">
                <select name="ircf_cat" onchange="this.form.submit()">
                    <option value="">همهٔ دسته‌ها</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= esc_attr($c->slug) ?>" <?= selected($cat, $c->slug, false) ?>><?= esc_html($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="ircf-btn">جست‌وجو</button>
            </form>

            <?php if (!$query->have_posts()): ?>
                <div class="ircf-empty">چیزی پیدا نشد.</div>
            <?php else: ?>
                <div class="ircf-recipe-grid">
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <a class="ircf-recipe-card" href="<?= esc_url(get_permalink()) ?>">
                            <div class="ircf-card-thumb">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else: ?>
                                    <span class="ircf-thumb-fallback">🍽️</span>
                                <?php endif; ?>
                            </div>
                            <div class="ircf-card-body">
                                <h3><?= esc_html(get_the_title()) ?></h3>
                                <div class="ircf-card-meta">
                                    <?php
                                    $prep = (int)get_post_meta(get_the_ID(), '_ircf_prep_minutes', true);
                                    $cook = (int)get_post_meta(get_the_ID(), '_ircf_cook_minutes', true);
                                    echo esc_html(($prep + $cook) . ' دقیقه');
                                    ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
