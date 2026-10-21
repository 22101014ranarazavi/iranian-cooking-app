<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin-side fields for a recipe: timing/servings/difficulty, the
 * ingredient list with exact quantities (for display), and the
 * numbered cooking steps.
 *
 * Which ingredients count for the "what can I cook" search is decided
 * separately, by which "مواد اولیه" taxonomy terms are checked in the
 * native WordPress sidebar box — that selection is what class-rest.php
 * matches against. This box is only for the human-readable amounts
 * shown on the recipe page, so list the same ingredients here that you
 * checked in the مواد اولیه box on the right, each with its amount.
 */
final class IRCF_Meta_Boxes
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'add_boxes']);
        add_action('save_post_recipe', [self::class, 'save']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(string $hook): void
    {
        global $post_type;
        if (!in_array($hook, ['post.php', 'post-new.php'], true) || $post_type !== 'recipe') {
            return;
        }
        wp_enqueue_script('ircf-admin', IRCF_URL . 'assets/js/admin.js', [], IRCF_VERSION, true);
        wp_enqueue_style('ircf-admin', IRCF_URL . 'assets/css/admin.css', [], IRCF_VERSION);
    }

    public static function add_boxes(): void
    {
        add_meta_box('ircf_details', 'اطلاعات تکمیلی', [self::class, 'render_details'], 'recipe', 'side', 'default');
        add_meta_box('ircf_ingredients', 'مواد لازم (با مقدار دقیق)', [self::class, 'render_ingredients'], 'recipe', 'normal', 'high');
        add_meta_box('ircf_steps', 'مراحل پخت', [self::class, 'render_steps'], 'recipe', 'normal', 'high');
    }

    public static function render_details(WP_Post $post): void
    {
        wp_nonce_field('ircf_save', 'ircf_nonce');
        $prep = get_post_meta($post->ID, '_ircf_prep_minutes', true);
        $cook = get_post_meta($post->ID, '_ircf_cook_minutes', true);
        $servings = get_post_meta($post->ID, '_ircf_servings', true);
        $difficulty = get_post_meta($post->ID, '_ircf_difficulty', true) ?: 'آسان';
        ?>
        <p>
            <label>زمان آماده‌سازی (دقیقه)<br>
            <input type="number" min="0" name="ircf_prep_minutes" value="<?= esc_attr($prep) ?>" style="width:100%"></label>
        </p>
        <p>
            <label>زمان پخت (دقیقه)<br>
            <input type="number" min="0" name="ircf_cook_minutes" value="<?= esc_attr($cook) ?>" style="width:100%"></label>
        </p>
        <p>
            <label>تعداد نفرات<br>
            <input type="number" min="1" name="ircf_servings" value="<?= esc_attr($servings) ?>" style="width:100%"></label>
        </p>
        <p>
            <label>سطح دشواری<br>
            <select name="ircf_difficulty" style="width:100%">
                <?php foreach (['آسان', 'متوسط', 'سخت'] as $level): ?>
                    <option value="<?= esc_attr($level) ?>" <?= selected($difficulty, $level, false) ?>><?= esc_html($level) ?></option>
                <?php endforeach; ?>
            </select>
            </label>
        </p>
        <?php
    }

    public static function render_ingredients(WP_Post $post): void
    {
        $lines = get_post_meta($post->ID, '_ircf_ingredient_lines', true);
        $text = is_array($lines) ? implode("\n", $lines) : '';
        ?>
        <p>هر خط یک ماده، به این شکل: <code>نام ماده: مقدار</code> — مثلاً <code>مرغ: ۴۰۰ گرم</code></p>
        <textarea name="ircf_ingredient_lines" rows="8" style="width:100%" placeholder="مرغ: ۴۰۰ گرم&#10;پیاز: ۱ عدد&#10;زعفران: یک چیمچه دم‌کرده"><?= esc_textarea($text) ?></textarea>
        <p class="description">این فهرست فقط برای نمایش روی صفحهٔ غذاست. برای اینکه این دستور در «پیشنهاد غذا» هم دیده شود، همین مواد را در باکس «مواد اولیه» (سمت راست صفحه) هم تیک بزنید.</p>
        <?php
    }

    public static function render_steps(WP_Post $post): void
    {
        $steps = get_post_meta($post->ID, '_ircf_steps', true);
        $steps = is_array($steps) && !empty($steps) ? $steps : [''];
        ?>
        <div id="ircf-steps-wrap">
            <?php foreach ($steps as $i => $step): ?>
                <div class="ircf-step-row">
                    <span class="ircf-step-num"><?= $i + 1 ?></span>
                    <textarea name="ircf_steps[]" rows="2"><?= esc_textarea($step) ?></textarea>
                    <button type="button" class="button ircf-remove-step">حذف</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-secondary" id="ircf-add-step">+ افزودن مرحله</button>
        <?php
    }

    public static function save(int $post_id): void
    {
        if (!isset($_POST['ircf_nonce']) || !wp_verify_nonce($_POST['ircf_nonce'], 'ircf_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_ircf_prep_minutes', (int)($_POST['ircf_prep_minutes'] ?? 0));
        update_post_meta($post_id, '_ircf_cook_minutes', (int)($_POST['ircf_cook_minutes'] ?? 0));
        update_post_meta($post_id, '_ircf_servings', (int)($_POST['ircf_servings'] ?? 0));

        $difficulty = sanitize_text_field($_POST['ircf_difficulty'] ?? 'آسان');
        update_post_meta($post_id, '_ircf_difficulty', $difficulty);

        $raw = (string)($_POST['ircf_ingredient_lines'] ?? '');
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), static fn($l) => $l !== ''));
        update_post_meta($post_id, '_ircf_ingredient_lines', array_map('sanitize_text_field', $lines));

        $steps = array_values(array_filter(array_map('trim', (array)($_POST['ircf_steps'] ?? [])), static fn($s) => $s !== ''));
        update_post_meta($post_id, '_ircf_steps', array_map('sanitize_textarea_field', $steps));

        // Flat list of selected "ingredient" term ids, used directly by the matcher.
        $terms = wp_get_post_terms($post_id, 'ingredient', ['fields' => 'ids']);
        if (!is_wp_error($terms)) {
            update_post_meta($post_id, '_ircf_ingredient_ids', $terms);
        }
    }
}
