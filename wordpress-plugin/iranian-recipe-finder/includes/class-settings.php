<?php
if (!defined('ABSPATH')) exit;

/**
 * One settings field: the DeepSeek API key, under
 * "دستورهای غذا" > "تنظیمات هوش مصنوعی". Stored as a normal WP option,
 * never written into plugin files.
 */
final class IRCF_Settings
{
    const OPTION = 'ircf_deepseek_api_key';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'add_page']);
        add_action('admin_init', [self::class, 'register_setting']);
    }

    public static function add_page(): void
    {
        add_submenu_page(
            'edit.php?post_type=recipe',
            'تنظیمات هوش مصنوعی',
            'تنظیمات هوش مصنوعی',
            'manage_options',
            'ircf-ai-settings',
            [self::class, 'render_page']
        );
    }

    public static function register_setting(): void
    {
        register_setting('ircf_ai_settings', self::OPTION, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
    }

    public static function get_api_key(): string
    {
        return (string) get_option(self::OPTION, '');
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $has_key = self::get_api_key() !== '';
        ?>
        <div class="wrap">
            <h1>تنظیمات هوش مصنوعی (DeepSeek)</h1>
            <p>
                این کلید برای بخش «✨ پیشنهاد هوش مصنوعی» استفاده می‌شود — پیشنهاد غذاهای تازه بر اساس مواد انتخابی کاربر،
                جدا از دستورهای ثابتی که خودتان در سایت ثبت کرده‌اید. کلید را از
                <a href="https://platform.deepseek.com" target="_blank" rel="noopener">platform.deepseek.com</a> بسازید.
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('ircf_ai_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ircf_api_key">DeepSeek API Key</label></th>
                        <td>
                            <input type="password" id="ircf_api_key" name="<?= esc_attr(self::OPTION) ?>"
                                   value="<?= esc_attr(self::get_api_key()) ?>" class="regular-text" autocomplete="off">
                            <p class="description">
                                <?= $has_key ? '✅ کلید تنظیم شده است.' : '⚠️ هنوز کلیدی وارد نشده — تا وارد نشود، بخش پیشنهاد هوش مصنوعی در سایت نمایش داده نمی‌شود.' ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('ذخیره'); ?>
            </form>
        </div>
        <?php
    }
}
