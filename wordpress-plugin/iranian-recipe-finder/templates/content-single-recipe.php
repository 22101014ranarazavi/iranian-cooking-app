<?php
if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()):
    the_post();
    $id = get_the_ID();
    $prep = (int)get_post_meta($id, '_ircf_prep_minutes', true);
    $cook = (int)get_post_meta($id, '_ircf_cook_minutes', true);
    $servings = (int)get_post_meta($id, '_ircf_servings', true);
    $difficulty = get_post_meta($id, '_ircf_difficulty', true) ?: 'آسان';
    $lines = get_post_meta($id, '_ircf_ingredient_lines', true) ?: [];
    $steps = get_post_meta($id, '_ircf_steps', true) ?: [];
    $category = get_the_terms($id, 'recipe_category');
    $category_name = ($category && !is_wp_error($category)) ? $category[0]->name : '';
    ?>
    <div class="ircf-wrap">
        <div class="ircf-recipe-hero">
            <div class="ircf-thumb-xl">
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('medium'); ?>
                <?php else: ?>
                    <span>🍽️</span>
                <?php endif; ?>
            </div>
            <div>
                <h1><?= esc_html(get_the_title()) ?></h1>
                <div class="ircf-meta-row">
                    <?php if ($category_name): ?><span>🍽️ <?= esc_html($category_name) ?></span><?php endif; ?>
                    <?php if ($prep): ?><span>⏱ آماده‌سازی <?= esc_html($prep) ?> دقیقه</span><?php endif; ?>
                    <?php if ($cook): ?><span>🔥 پخت <?= esc_html($cook) ?> دقیقه</span><?php endif; ?>
                    <?php if ($servings): ?><span>👥 <?= esc_html($servings) ?> نفر</span><?php endif; ?>
                    <span>📊 <?= esc_html($difficulty) ?></span>
                </div>
            </div>
        </div>

        <div class="ircf-recipe-columns">
            <div class="ircf-ingredients-box">
                <h2>مواد لازم</h2>
                <ul>
                    <?php foreach ($lines as $line): ?>
                        <?php
                        $parts = explode(':', $line, 2);
                        $name = trim($parts[0] ?? $line);
                        $qty = trim($parts[1] ?? '');
                        ?>
                        <li><span><?= esc_html($name) ?></span><span class="qty"><?= esc_html($qty) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="ircf-steps-box">
                <h2>مراحل پخت</h2>
                <ol>
                    <?php foreach ($steps as $step): ?>
                        <li><?= esc_html($step) ?></li>
                    <?php endforeach; ?>
                </ol>
                <?php if (get_the_content()): ?>
                    <div class="ircf-extra-notes"><?php the_content(); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
endwhile;

get_footer();
