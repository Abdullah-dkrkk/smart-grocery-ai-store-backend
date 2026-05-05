<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$variationTypes = [
    'packaged_front' => 'professional product photography, front view, packaged in retail packaging, studio lighting, white background',
    'packaged_angle' => 'professional product photography, 45 degree angle view, packaged in retail packaging, soft lighting, clean background',
    'lifestyle' => 'lifestyle product photography, placed in modern kitchen setting, natural lighting, home environment',
    'closeup' => 'extreme close-up product photography, showing texture and details, macro shot, professional lighting',
    'ingredients' => 'product photography with raw ingredients displayed around it, flat lay composition, natural lighting',
    'prepared' => 'professional food photography, product prepared and ready to consume, beautiful plating',
    'white_background' => 'product photography, isolated on pure white background, e-commerce style, clean studio shot',
];

$startFrom = $argv[1] ?? 0;
$endAt = $argv[2] ?? 100;
$logFile = __DIR__ . '/image-generation.log';

$log = function ($message) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}";
    echo $line . PHP_EOL;
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
};

$log("=== Image Generation Started ===");
$log("Processing products from ID {$startFrom} to {$endAt}");

$products = Product::orderBy('id')
    ->where('id', '>=', $startFrom)
    ->where('id', '<=', $endAt)
    ->get();

$log("Found {$products->count()} products to process");

$totalSuccess = 0;
$totalFailed = 0;

foreach ($products as $product) {
    $log("Processing: {$product->name} (ID: {$product->id})");

    foreach ($variationTypes as $variationType => $suffix) {
        $filename = Str::slug($product->name) . '-' . Str::snake($variationType) . '.jpg';
        $relativePath = 'images/products/variations/' . $filename;
        $fullPath = public_path($relativePath);

        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($fullPath)) {
            $log("  [SKIP] {$variationType} - already exists");
            $totalSuccess++;
            continue;
        }

        $prompt = urlencode("High quality organic food product: {$product->name}, {$suffix}");
        $seed = crc32($product->slug . '-' . $variationType) % 10000;
        $pollinationUrl = "https://image.pollinations.ai/prompt/{$prompt}?width=800&height=800&seed={$seed}&model=flux&nologo=true";

        $log("  [DOWNLOAD] {$variationType}");

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $pollinationUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result !== false && $httpCode === 200 && strlen($result) > 1000) {
            file_put_contents($fullPath, $result);

            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'variation_type' => $variationType,
                ],
                [
                    'image_url' => $relativePath,
                    'alt_text' => "{$product->name} - " . ucwords(str_replace('_', ' ', $variationType)),
                    'is_primary' => $variationType === 'packaged_front',
                    'sort_order' => array_search($variationType, array_keys($variationTypes)) + 1,
                ]
            );

            $totalSuccess++;
            $log("  [OK] {$variationType}");
        } else {
            $totalFailed++;
            $log("  [FAIL] {$variationType} - HTTP:{$httpCode} {$curlError}");
        }

        sleep(2);
    }

    $log("Completed: {$product->name}");
}

$log("=== Generation Complete ===");
$log("Success: {$totalSuccess}, Failed: {$totalFailed}");
