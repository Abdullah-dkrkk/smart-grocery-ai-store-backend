<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductVariationImageSeeder extends Seeder
{
    private array $variationTypes = [
        'packaged_front' => 'professional product photography, front view, packaged in retail packaging, studio lighting, white background',
        'packaged_angle' => 'professional product photography, 45 degree angle view, packaged in retail packaging, soft lighting, clean background',
        'lifestyle' => 'lifestyle product photography, placed in modern kitchen setting, natural lighting, home environment',
        'closeup' => 'extreme close-up product photography, showing texture and details, macro shot, professional lighting',
        'ingredients' => 'product photography with raw ingredients displayed around it, flat lay composition, natural lighting',
        'prepared' => 'professional food photography, product prepared and ready to consume, beautiful plating',
        'white_background' => 'product photography, isolated on pure white background, e-commerce style, clean studio shot',
    ];

    public function run(): void
    {
        $products = Product::orderBy('id')->limit(10)->get();

        if ($products->isEmpty()) {
            $this->command->error('No products found. Run ProductSeeder first.');

            return;
        }

        $totalImages = 0;
        $failedImages = 0;

        foreach ($products as $product) {
            $this->command->info("Processing: {$product->name} (ID: {$product->id})");

            foreach ($this->variationTypes as $variationType => $suffix) {
                $filename = $this->generateFilename($product->name, $variationType);
                $relativePath = 'images/products/variations/' . $filename;
                $fullPath = public_path($relativePath);

                $directory = dirname($fullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                if (file_exists($fullPath)) {
                    $this->command->comment("  Skipping (exists): {$variationType}");
                    $totalImages++;
                    continue;
                }

                $prompt = "High quality organic food product: {$product->name}, {$suffix}";
                $seed = crc32($product->slug . '-' . $variationType) % 10000;
                $pollinationUrl = $this->buildPollinationUrl($prompt, $seed);

                $this->command->comment("  Downloading: {$variationType}");

                $imageData = $this->downloadImage($pollinationUrl);

                if ($imageData !== false) {
                    file_put_contents($fullPath, $imageData);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $relativePath,
                        'variation_type' => $variationType,
                        'alt_text' => "{$product->name} - " . ucwords(str_replace('_', ' ', $variationType)),
                        'is_primary' => $variationType === 'packaged_front',
                        'sort_order' => array_search($variationType, array_keys($this->variationTypes)) + 1,
                    ]);

                    $totalImages++;
                    $this->command->info("  ✓ Saved: {$variationType}");
                } else {
                    $failedImages++;
                    $this->command->warn("  ✗ Failed: {$variationType}");
                }
            }

            $this->command->line('');
        }

        $this->command->info("=== Summary ===");
        $this->command->info("Total variation images: {$totalImages}");
        if ($failedImages > 0) {
            $this->command->warn("Failed downloads: {$failedImages}");
        }
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

        if ($result === false || $httpCode !== 200) {
            $this->command->warn("  cURL error ({$httpCode}): {$curlError}");

            return false;
        }

        if (strlen($result) < 1000) {
            return false;
        }

        return $result;
    }

    private function generateFilename(string $productName, string $variationType): string
    {
        $slug = Str::slug($productName);
        $suffix = Str::snake($variationType);

        return "{$slug}-{$suffix}.jpg";
    }
}
