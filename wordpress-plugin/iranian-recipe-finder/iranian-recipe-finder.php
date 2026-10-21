<?php
/**
 * Plugin Name: آشپزی ایرانی — پیشنهاد غذا بر اساس مواد موجود
 * Plugin URI: https://example.com
 * Description: نوع نوشتهٔ «دستور غذا» به همراه طبقه‌بندی مواد اولیه و ابزار پیشنهاد غذا بر اساس موادی که کاربر در خانه دارد.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: —
 * Text Domain: ircf
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IRCF_VERSION', '1.0.0');
define('IRCF_PATH', plugin_dir_path(__FILE__));
define('IRCF_URL', plugin_dir_url(__FILE__));

require_once IRCF_PATH . 'includes/class-matcher.php';
require_once IRCF_PATH . 'includes/class-cpt.php';
require_once IRCF_PATH . 'includes/class-meta-boxes.php';
require_once IRCF_PATH . 'includes/class-rest.php';
require_once IRCF_PATH . 'includes/class-settings.php';
require_once IRCF_PATH . 'includes/class-ai.php';
require_once IRCF_PATH . 'includes/class-shortcode.php';
require_once IRCF_PATH . 'includes/class-template.php';
require_once IRCF_PATH . 'includes/class-seeder.php';

final class Iranian_Recipe_Finder
{
    public static function init(): void
    {
        IRCF_CPT::register();
        IRCF_Meta_Boxes::register();
        IRCF_REST::register();
        IRCF_Settings::register();
        IRCF_AI::register();
        IRCF_Shortcode::register();
        IRCF_Template::register();
    }

    public static function activate(): void
    {
        IRCF_CPT::register_post_type();
        IRCF_CPT::register_taxonomies();
        IRCF_Seeder::seed_if_empty();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}

add_action('init', ['Iranian_Recipe_Finder', 'init']);
register_activation_hook(__FILE__, ['Iranian_Recipe_Finder', 'activate']);
register_deactivation_hook(__FILE__, ['Iranian_Recipe_Finder', 'deactivate']);
