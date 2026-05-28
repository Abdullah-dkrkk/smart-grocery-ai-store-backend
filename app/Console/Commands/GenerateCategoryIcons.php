<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class GenerateCategoryIcons extends Command
{
    protected $signature = 'app:generate-category-icons
                             {--force : Regenerate existing icons}';

    protected $description = 'Download professional SVG icons for all parent categories from Lucide CDN';

    private string $iconsDir;

    private array $iconMap = [
        'dairy-eggs'       => ['icon' => 'milk',       'color' => '#059669'],
        'fresh-produce'    => ['icon' => 'apple',       'color' => '#059669'],
        'meat-seafood'     => ['icon' => 'fish',        'color' => '#059669'],
        'bakery'           => ['icon' => 'croissant',   'color' => '#059669'],
        'health-wellness'  => ['icon' => 'heart-pulse', 'color' => '#059669'],
        'pantry-staples'   => ['icon' => 'package',     'color' => '#059669'],
        'snacks'           => ['icon' => 'cookie',      'color' => '#059669'],
        'beverages'        => ['icon' => 'cup-soda',    'color' => '#059669'],
        'frozen-foods'     => ['icon' => 'snowflake',   'color' => '#059669'],
        'specialty-diet'   => ['icon' => 'leaf',        'color' => '#059669'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->iconsDir = public_path('images/categories');
    }

    public function handle(): int
    {
        $force = $this->option('force');

        if (!is_dir($this->iconsDir)) {
            mkdir($this->iconsDir, 0755, true);
        }

        $categories = Category::whereNull('parent_id')->orderBy('id')->get();

        if ($categories->isEmpty()) {
            $this->error('No parent categories found.');
            return Command::FAILURE;
        }

        $this->info("=== Category SVG Icon Generator (Lucide) ===");
        $this->info("Categories to process: {$categories->count()}");
        $this->line('');

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($categories as $category) {
            $slug = $category->slug;
            $filename = "{$slug}.svg";
            $fullPath = "{$this->iconsDir}/{$filename}";

            if (!$force && file_exists($fullPath)) {
                $this->comment("  – Skipped {$slug} (exists)");
                $skipped++;
                continue;
            }

            $iconName = $this->iconMap[$slug]['icon'] ?? null;
            $color = $this->iconMap[$slug]['color'] ?? '#6B7280';

            if (!$iconName) {
                $this->warn("  ? No icon mapping for {$slug}, skipping");
                $skipped++;
                continue;
            }

            $cdlUrl = "https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/{$iconName}.svg";
            $this->comment("  → Downloading: {$iconName}.svg for {$slug}");

            $svgContent = $this->downloadSvg($cdlUrl);

            if ($svgContent === false) {
                $this->warn("  ✗ Failed to download {$iconName}.svg");
                $failed++;
                continue;
            }

            $svgContent = $this->applyColor($svgContent, $color);
            $svgContent = $this->addSize($svgContent, 24);
            $svgContent = $this->thinStroke($svgContent);

            file_put_contents($fullPath, $svgContent);

            $relativePath = "images/categories/{$filename}";
            $category->update(['image_url' => $relativePath]);

            $this->info("  ✓ Saved: {$filename}");
            $generated++;
        }

        $this->line('');
        $this->info("=== Summary ===");
        $this->info("Generated: {$generated}");
        $this->info("Skipped:   {$skipped}");
        $this->info("Failed:    {$failed}");
        $this->info("Location:  {$this->iconsDir}");

        return Command::SUCCESS;
    }

    private function downloadSvg(string $url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            return false;
        }

        return $result;
    }

    private function applyColor(string $svg, string $color): string
    {
        return preg_replace('/stroke="currentColor"/', "stroke=\"{$color}\"", $svg);
    }

    private function addSize(string $svg, int $size): string
    {
        $svg = preg_replace('/(?<!-)width="\d+"/', "width=\"{$size}\"", $svg);
        $svg = preg_replace('/(?<!-)height="\d+"/', "height=\"{$size}\"", $svg);
        return $svg;
    }

    private function thinStroke(string $svg): string
    {
        return preg_replace('/stroke-width="2"/', 'stroke-width="1.5"', $svg);
    }
}
