<?php
if (!defined('ABSPATH')) exit;

/**
 * POST /wp-json/ircf/v1/ai-suggest  { ingredients: string[] }
 * -> { suggestions: [...] } or { error: string }
 *
 * Same contract as demo/api/ai-suggest.php. Separate from the catalog
 * matcher (class-rest.php): this can propose dishes that don't exist
 * as "recipe" posts at all, clearly labeled in the UI as AI-generated.
 */
final class IRCF_AI
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route('ircf/v1', '/ai-suggest', [
            'methods' => 'POST',
            'callback' => [self::class, 'suggest'],
            'permission_callback' => '__return_true',
            'args' => [
                'ingredients' => ['required' => true],
            ],
        ]);
    }

    public static function suggest(WP_REST_Request $request): WP_REST_Response
    {
        $api_key = IRCF_Settings::get_api_key();
        if ($api_key === '') {
            return new WP_REST_Response([
                'error' => 'کلید DeepSeek هنوز در تنظیمات افزونه وارد نشده است.',
            ], 503);
        }

        $ingredients = array_values(array_filter(array_map('sanitize_text_field', (array)$request->get_param('ingredients'))));
        if (empty($ingredients)) {
            return new WP_REST_Response(['suggestions' => []]);
        }

        $system = "تو یک دستیار آشپزی ایرانی هستی. کاربر فهرستی از مواد اولیه‌ای که الان در خانه دارد را می‌دهد.\n"
            . "۱ تا ۳ پیشنهاد غذای خلاقانه (ترجیحاً ایرانی یا قابل تهیه با سبک آشپزی ایرانی) بده که عمدتاً با همین مواد "
            . "(به‌علاوهٔ نمک/فلفل/روغن/آب که همیشه موجود فرض می‌شوند) قابل تهیه باشد.\n"
            . "فقط و فقط یک خروجی JSON معتبر با این ساختار برگردان، بدون هیچ متن اضافه:\n"
            . '{"suggestions":[{"title":"نام غذا","note":"یک جملهٔ کوتاه دربارهٔ اینکه چرا این مواد مناسبش هستند","ingredients":["ماده ۱","ماده ۲"],"steps":["مرحله ۱","مرحله ۲","مرحله ۳"]}]}';

        $response = wp_remote_post('https://api.deepseek.com/chat/completions', [
            'timeout' => 25,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => 'مواد موجود: ' . implode('، ', $ingredients)],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
                'max_tokens' => 900,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => 'اتصال به DeepSeek برقرار نشد: ' . $response->get_error_message()], 502);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_REST_Response(['error' => 'DeepSeek خطا داد (کد ' . $code . ').'], 502);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if (!is_array($parsed) || !isset($parsed['suggestions'])) {
            return new WP_REST_Response(['error' => 'پاسخ DeepSeek قابل‌فهم نبود.'], 502);
        }

        return new WP_REST_Response(['suggestions' => $parsed['suggestions']]);
    }
}
