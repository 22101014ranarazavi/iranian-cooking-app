<?php
if (!defined('ABSPATH')) exit;

/**
 * Renders the single recipe page. If the active theme ships its own
 * single-recipe.php, that takes priority — this is only the fallback,
 * so the plugin looks right on day one with any theme.
 */
final class IRCF_Template
{
    public static function register(): void
    {
        add_filter('template_include', [self::class, 'maybe_use_plugin_template']);
        add_action('wp_enqueue_scripts', [self::class, 'maybe_enqueue_styles']);
    }

    public static function maybe_enqueue_styles(): void
    {
        if (is_singular('recipe')) {
            wp_enqueue_style('ircf-frontend', IRCF_URL . 'assets/css/frontend.css', [], IRCF_VERSION);
        }
    }

    public static function maybe_use_plugin_template(string $template): string
    {
        if (!is_singular('recipe')) {
            return $template;
        }

        $theme_override = locate_template('single-recipe.php');
        if ($theme_override) {
            return $theme_override;
        }

        return IRCF_PATH . 'templates/content-single-recipe.php';
    }
}
