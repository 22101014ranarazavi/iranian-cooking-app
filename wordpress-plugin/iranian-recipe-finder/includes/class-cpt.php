<?php
if (!defined('ABSPATH')) exit;

/**
 * "recipe" custom post type, plus two taxonomies:
 *  - ingredient: hierarchical (group -> ingredient), what the matcher runs on
 *  - recipe_category: hierarchical (خورشت/کباب/...), for browsing & search
 */
final class IRCF_CPT
{
    public static function register(): void
    {
        self::register_post_type();
        self::register_taxonomies();
    }

    public static function register_post_type(): void
    {
        register_post_type('recipe', [
            'labels' => [
                'name' => 'دستورهای غذا',
                'singular_name' => 'دستور غذا',
                'add_new' => 'افزودن دستور غذا',
                'add_new_item' => 'افزودن دستور غذای جدید',
                'edit_item' => 'ویرایش دستور غذا',
                'new_item' => 'دستور غذای جدید',
                'view_item' => 'مشاهدهٔ دستور غذا',
                'search_items' => 'جست‌وجوی دستور غذا',
                'not_found' => 'دستور غذایی پیدا نشد',
                'menu_name' => 'دستورهای غذا',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'recipes'],
            'menu_icon' => 'dashicons-carrot',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ]);
    }

    public static function register_taxonomies(): void
    {
        register_taxonomy('ingredient', ['recipe'], [
            'labels' => [
                'name' => 'مواد اولیه',
                'singular_name' => 'مادهٔ اولیه',
                'menu_name' => 'مواد اولیه',
                'search_items' => 'جست‌وجوی مواد اولیه',
                'all_items' => 'همهٔ مواد اولیه',
                'parent_item' => 'گروه مادر',
                'edit_item' => 'ویرایش ماده',
                'add_new_item' => 'افزودن مادهٔ جدید',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'ingredient'],
        ]);

        register_taxonomy('recipe_category', ['recipe'], [
            'labels' => [
                'name' => 'دسته‌بندی غذا',
                'singular_name' => 'دسته',
                'menu_name' => 'دسته‌بندی غذا',
                'all_items' => 'همهٔ دسته‌ها',
                'edit_item' => 'ویرایش دسته',
                'add_new_item' => 'افزودن دستهٔ جدید',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'recipe-category'],
        ]);
    }
}
