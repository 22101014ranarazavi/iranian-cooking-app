<?php
/**
 * POST { ingredients: string[] } -> JSON { suggestions: [...] } or { error: string }
 *
 * Asks DeepSeek for 1-3 creative recipe ideas using (mostly) the given
 * ingredients. This is separate from the catalog matcher (api/match.php):
 * it can suggest dishes that are not in the site's own recipe database
 * at all, clearly labeled in the UI as AI-generated / not admin-reviewed.
 */

header('Content-Type: application/json; charset=utf-8');

$apiKey = getenv('DEEPSEEK_API_KEY') ?: '';
if ($apiKey === '') {
    $configFile = __DIR__ . '/../config.php';
    $apiKey = is_file($configFile) ? (require $configFile)['deepseek_api_key'] ?? '' : '';
}

if ($apiKey === '') {
    http_response_code(503);
    echo json_encode([
        'error' => 'کلید DeepSeek هنوز تنظیم نشده. demo/config.php.example را به demo/config.php کپی کنید و کلید را در آن بگذارید.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = json_decode(file_get_contents('php://input'), true);
$ingredients = array_values(array_filter(array_map('trim', (array)($raw['ingredients'] ?? []))));

if (empty($ingredients)) {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$system = <<<SYS
تو یک دستیار آشپزی ایرانی هستی. کاربر فهرستی از مواد اولیه‌ای که الان در خانه دارد را می‌دهد.
۱ تا ۳ پیشنهاد غذای خلاقانه (ترجیحاً ایرانی یا قابل تهیه با سبک آشپزی ایرانی) بده که عمدتاً با همین مواد (به‌علاوهٔ نمک/فلفل/روغن/آب که همیشه موجود فرض می‌شوند) قابل تهیه باشد.
فقط و فقط یک خروجی JSON معتبر با این ساختار برگردان، بدون هیچ متن اضافه:
{"suggestions":[{"title":"نام غذا","note":"یک جملهٔ کوتاه دربارهٔ اینکه چرا این مواد مناسبش هستند","ingredients":["ماده ۱","ماده ۲"],"steps":["مرحله ۱","مرحله ۲","مرحله ۳"]}]}
SYS;

$payload = json_encode([
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => 'مواد موجود: ' . implode('، ', $ingredients)],
    ],
    'response_format' => ['type' => 'json_object'],
    'temperature' => 0.7,
    'max_tokens' => 900,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init('https://api.deepseek.com/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 25,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'اتصال به DeepSeek برقرار نشد: ' . $curlError], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    echo json_encode(['error' => 'DeepSeek خطا داد (کد ' . $httpCode . ').', 'detail' => $response], JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode($response, true);
$content = $decoded['choices'][0]['message']['content'] ?? '';
$parsed = json_decode($content, true);

if (!is_array($parsed) || !isset($parsed['suggestions'])) {
    http_response_code(502);
    echo json_encode(['error' => 'پاسخ DeepSeek قابل‌فهم نبود.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['suggestions' => $parsed['suggestions']], JSON_UNESCAPED_UNICODE);
