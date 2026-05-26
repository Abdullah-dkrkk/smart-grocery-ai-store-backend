<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateVariationImages extends Command
{
    protected $signature = 'app:generate-variation-images
                            {--from-id= : Start from specific product ID}
                            {--force : Regenerate existing images}';

    protected $description = 'Generate AI variation images for all products using Pollinations.ai';

    private array $variationTypes = [
        'packaged_front' => 'professional product photography, front view, packaged in retail packaging, studio lighting, white background',
        'packaged_angle' => 'professional product photography, 45 degree angle view, packaged in retail packaging, soft lighting, clean background',
        'lifestyle' => 'lifestyle product photography, placed in modern kitchen setting, natural lighting, home environment',
        'closeup' => 'extreme close-up product photography, showing texture and details, macro shot, professional lighting',
        'ingredients' => 'product photography with raw ingredients displayed around it, flat lay composition, natural lighting',
        'prepared' => 'professional food photography, product prepared and ready to consume, beautiful plating',
        'white_background' => 'product photography, isolated on pure white background, e-commerce style, clean studio shot',
    ];

    private string $progressFile;
    private string $variationsDir;

    public function __construct()
    {
        parent::__construct();
        $this->progressFile = storage_path('app/variation-progress.json');
        $this->variationsDir = public_path('images/products/variations');
    }

    public function handle(): int
    {
        $fromId = (int) $this->option('from-id') ?: null;
        $force = $this->option('force');

        if (!is_dir($this->variationsDir)) {
            mkdir($this->variationsDir, 0755, true);
        }

        $query = Product::orderBy('id');
        if ($fromId) {
            $query->where('id', '>=', $fromId);
        }
        $totalProducts = $query->count();
        $products = $query->get();

        if ($products->isEmpty()) {
            $this->error('No products found.');
            return Command::FAILURE;
        }

        $progress = $this->loadProgress();
        $startId = $fromId ?? ($progress['last_completed_id'] ?? 0) + 1;

        $this->info("=== Variation Image Generator ===");
        $this->info("Total products to process: {$totalProducts}");
        $this->info("Starting from product ID: {$startId}");
        $this->info("Progress file: {$this->progressFile}");
        $this->line('');

        $totalImages = $progress['total_success'] ?? 0;
        $failedImages = $progress['total_failed'] ?? 0;
        $skippedImages = $progress['total_skipped'] ?? 0;

        foreach ($products as $product) {
            if ($product->id < $startId) {
                continue;
            }

            $this->comment("──────────────────────────────────────────");
            $this->info("[{$product->id}/{$products->last()->id}] Processing: {$product->name}");

            $productResult = $this->processProduct($product, $force);

            $totalImages += $productResult['success'];
            $failedImages += $productResult['failed'];
            $skippedImages += $productResult['skipped'];

            $this->saveProgress([
                'last_completed_id' => $product->id,
                'last_product_name' => $product->name,
                'total_success' => $totalImages,
                'total_failed' => $failedImages,
                'total_skipped' => $skippedImages,
                'updated_at' => now()->toIso8601String(),
            ]);

            $this->line("  Summary: ✓{$productResult['success']} ✗{$productResult['failed']} –{$productResult['skipped']}");
            $this->line('');
        }

        $this->info("========================================");
        $this->info("=== FINAL SUMMARY ===");
        $this->info("Total successful: {$totalImages}");
        $this->info("Total skipped:    {$skippedImages}");
        $this->info("Total failed:     {$failedImages}");
        $this->info("========================================");

        if ($failedImages > 0) {
            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

    private function processProduct(Product $product, bool $force): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->variationTypes as $variationType => $suffix) {
            $filename = $this->generateFilename($product->slug, $variationType);
            $relativePath = 'images/products/variations/' . $filename;
            $fullPath = $this->variationsDir . '/' . $filename;

            $existingRecord = ProductImage::where('product_id', $product->id)
                ->where('variation_type', $variationType)
                ->exists();

            if (!$force && $existingRecord) {
                $this->comment("  – Skipped {$variationType} (exists)");
                $skipped++;
                continue;
            }

            if (!$force && file_exists($fullPath) && !$existingRecord) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $relativePath,
                    'variation_type' => $variationType,
                    'alt_text' => "{$product->name} - " . ucwords(str_replace('_', ' ', $variationType)),
                    'is_primary' => $variationType === 'packaged_front',
                    'sort_order' => array_search($variationType, array_keys($this->variationTypes)) + 1,
                ]);
                $this->comment("  – Recovered {$variationType} (file existed, created DB record)");
                $skipped++;
                continue;
            }

            if (!$force && file_exists($fullPath)) {
                $this->comment("  – Skipped {$variationType} (exists)");
                $skipped++;
                continue;
            }

            $prompt = "High quality organic food product: {$product->name}, {$suffix}";
            $seed = abs(crc32($product->slug . '-' . $variationType)) % 10000;
            $imageUrl = $this->buildPollinationUrl($prompt, $seed);

            $imageData = false;
            $maxRetries = 5;
            $attempt = 0;

            while ($attempt < $maxRetries && $imageData === false) {
                if ($attempt > 0) {
                    $delay = 10;
                    $this->comment("  → Retry {$attempt}/{$maxRetries} after {$delay}s...");
                    sleep($delay);
                }

                $attempt++;
                $this->comment("  → Downloading: {$variationType} (attempt {$attempt})");
                $imageData = $this->downloadImage($imageUrl);

                if ($imageData === false) {
                    $this->warn("  ✗ Attempt {$attempt} failed");
                }
            }

            if ($imageData !== false) {
                file_put_contents($fullPath, $imageData);

                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'variation_type' => $variationType,
                    ],
                    [
                        'image_url' => $relativePath,
                        'alt_text' => "{$product->name} - " . ucwords(str_replace('_', ' ', $variationType)),
                        'is_primary' => $variationType === 'packaged_front',
                        'sort_order' => array_search($variationType, array_keys($this->variationTypes)) + 1,
                    ]
                );

                $this->info("  ✓ Saved: {$variationType}");
                $success++;
            } else {
                $this->warn("  ✗ Failed: {$variationType} after {$maxRetries} attempts");
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    private function buildPollinationUrl(string $prompt, int $seed): string
    {
        $encodedPrompt = urlencode($prompt);
        return "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=800&height=800&seed={$seed}&model=flux&nologo=true";
    }

    private function downloadImage(string $url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_LOW_SPEED_TIME => 30,
            CURLOPT_LOW_SPEED_LIMIT => 100,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            $this->warn("  cURL error ({$httpCode}): {$curlError}");
            return false;
        }

        if (strlen($result) < 1000) {
            return false;
        }

        return $result;
    }

    private function generateFilename(string $slug, string $variationType): string
    {
        return "{$slug}-{$variationType}.jpg";
    }

    private function loadProgress(): array
    {
        if (file_exists($this->progressFile)) {
            $data = json_decode(file_get_contents($this->progressFile), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'last_completed_id' => 0,
            'last_product_name' => null,
            'total_success' => 0,
            'total_failed' => 0,
            'total_skipped' => 0,
            'updated_at' => null,
        ];
    }

    private function saveProgress(array $data): void
    {
        file_put_contents(
            $this->progressFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
