<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GenerateProductImages extends Command
{
    protected $signature = 'app:generate-product-images 
                            {count=10 : Number of products to generate}
                            {--force : Skip existing images}';
    protected $description = 'Generate AI product images using Pollinations.ai';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $this->info("Generating {$count} AI product images using Pollinations.ai...");

        $products = $this->getTestProducts();
        $selectedProducts = array_slice($products, 0, $count);

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $publicPath = public_path('images/products');
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        foreach ($selectedProducts as $productData) {
            $filename = Str::slug($productData['name']) . '.jpg';
            $filePath = "{$publicPath}/{$filename}";

            if (file_exists($filePath) && !$this->option('force')) {
                $this->warn("Skipping: {$productData['name']} (image exists)");
                continue;
            }

            $prompt = urlencode("Professional product photography of {$productData['name']}, {$productData['description_short']}, clean white background, studio lighting, photorealistic, high quality, e-commerce style, 4k");
            $imageUrl = "https://image.pollinations.ai/prompt/{$prompt}?width=1024&height=1024&seed={$productData['seed']}&model=flux&nologo=true";

            try {
                $curlPath = 'curl.exe';
                $curlCmd = sprintf(
                    '%s -L -s -o "%s" "%s" --connect-timeout 60 --max-time 300',
                    $curlPath,
                    addslashes($filePath),
                    addslashes($imageUrl)
                );

                exec($curlCmd . ' 2>&1', $output, $exitCode);

                if ($exitCode === 0 && file_exists($filePath) && filesize($filePath) > 1000) {
                    $productData['image_url'] = "images/products/{$filename}";
                    $this->createProduct($productData);
                } else {
                    $this->error("Failed: {$productData['name']} (curl exit code: {$exitCode})");
                }
            } catch (\Exception $e) {
                $this->error("Error: {$productData['name']} - {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Check storage/app/public/products/");

        return Command::SUCCESS;
    }

    private function createProduct(array $data): void
    {
        Product::firstOrCreate(
            ['sku' => $data['sku']],
            [
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'],
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'stock_quantity' => rand(50, 300),
                'is_active' => true,
                'is_featured' => true,
                'nutrition_data' => $data['nutrition_data'],
                'category_id' => 1,
                'vendor_id' => 2,
                'image_url' => $data['image_url'],
            ]
        );
    }

    private function getTestProducts(): array
    {
        return [
            [
                'name' => 'Organic Almond Milk Unsweetened',
                'description' => 'Fresh organic almond milk with no added sugar. Perfect for smoothies, coffee, and cereals.',
                'description_short' => 'almond milk bottle, minimalist packaging',
                'price' => 4.99,
                'sku' => 'ORG-ALM-001',
                'seed' => 1001,
                'nutrition_data' => ['calories' => 30, 'protein' => 1, 'carbs' => 1, 'fats' => 2.5, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['tree nuts']],
            ],
            [
                'name' => 'Grass-Fed Whey Protein Isolate',
                'description' => 'Premium grass-fed whey protein isolate for muscle building and recovery. 25g protein per serving.',
                'description_short' => 'protein powder tub, sleek black container',
                'price' => 39.99,
                'sku' => 'PRO-WHY-002',
                'seed' => 1002,
                'nutrition_data' => ['calories' => 120, 'protein' => 25, 'carbs' => 2, 'fats' => 1, 'sugar' => 1, 'fiber' => 0, 'allergens' => ['dairy']],
            ],
            [
                'name' => 'Organic Chia Seeds Premium',
                'description' => 'Premium organic chia seeds packed with omega-3, fiber, and protein.',
                'description_short' => 'chia seeds in transparent glass jar, eco packaging',
                'price' => 9.99,
                'sku' => 'SEE-CHI-003',
                'seed' => 1003,
                'nutrition_data' => ['calories' => 138, 'protein' => 5, 'carbs' => 12, 'fats' => 9, 'sugar' => 0, 'fiber' => 10, 'allergens' => []],
            ],
            [
                'name' => 'Cold-Pressed Olive Oil Extra Virgin',
                'description' => 'Premium extra virgin olive oil, cold-pressed for maximum flavor and nutrition.',
                'description_short' => 'olive oil glass bottle, elegant green tint',
                'price' => 14.99,
                'sku' => 'OIL-OLV-004',
                'seed' => 1004,
                'nutrition_data' => ['calories' => 120, 'protein' => 0, 'carbs' => 0, 'fats' => 14, 'sugar' => 0, 'fiber' => 0, 'allergens' => []],
            ],
            [
                'name' => 'Organic Greek Yogurt Plain',
                'description' => 'Creamy Greek-style yogurt with no added sugar. High in protein and probiotics.',
                'description_short' => 'yogurt cup, white minimalist packaging',
                'price' => 3.99,
                'sku' => 'DAI-YOG-005',
                'seed' => 1005,
                'nutrition_data' => ['calories' => 100, 'protein' => 17, 'carbs' => 6, 'fats' => 0.5, 'sugar' => 4, 'fiber' => 0, 'allergens' => ['dairy']],
            ],
            [
                'name' => 'Raw Wildflower Honey',
                'description' => 'Pure raw wildflower honey, unprocessed and packed with natural enzymes.',
                'description_short' => 'honey jar with wooden dipper, golden amber',
                'price' => 11.99,
                'sku' => 'HNY-WLD-006',
                'seed' => 1006,
                'nutrition_data' => ['calories' => 64, 'protein' => 0, 'carbs' => 17, 'fats' => 0, 'sugar' => 17, 'fiber' => 0, 'allergens' => []],
            ],
            [
                'name' => 'Organic Rolled Oats',
                'description' => 'Whole grain rolled oats, perfect for overnight oats and healthy breakfast bowls.',
                'description_short' => 'oats package, kraft paper pouch, natural look',
                'price' => 5.99,
                'sku' => 'GRN-OAT-007',
                'seed' => 1007,
                'nutrition_data' => ['calories' => 150, 'protein' => 5, 'carbs' => 27, 'fats' => 3, 'sugar' => 1, 'fiber' => 4, 'allergens' => ['oats']],
            ],
            [
                'name' => 'Organic Peanut Butter Creamy',
                'description' => 'Smooth and creamy organic peanut butter made from roasted peanuts. No added sugar or oil.',
                'description_short' => 'peanut butter jar, rustic label, creamy texture',
                'price' => 7.49,
                'sku' => 'NUT-PBT-008',
                'seed' => 1008,
                'nutrition_data' => ['calories' => 190, 'protein' => 7, 'carbs' => 7, 'fats' => 16, 'sugar' => 3, 'fiber' => 2, 'allergens' => ['peanuts']],
            ],
            [
                'name' => 'Organic Quinoa Grain',
                'description' => 'Premium organic quinoa. High in protein, fiber, and essential amino acids. Gluten-free.',
                'description_short' => 'quinoa in clear bag, premium packaging',
                'price' => 8.99,
                'sku' => 'GRA-QUI-009',
                'seed' => 1009,
                'nutrition_data' => ['calories' => 222, 'protein' => 8, 'carbs' => 39, 'fats' => 3.5, 'sugar' => 0, 'fiber' => 5, 'allergens' => []],
            ],
            [
                'name' => 'Organic Coconut Oil Virgin',
                'description' => 'Cold-pressed virgin coconut oil, perfect for cooking, baking, and skincare.',
                'description_short' => 'coconut oil glass jar, tropical design',
                'price' => 9.49,
                'sku' => 'OIL-COC-010',
                'seed' => 1010,
                'nutrition_data' => ['calories' => 120, 'protein' => 0, 'carbs' => 0, 'fats' => 14, 'sugar' => 0, 'fiber' => 0, 'allergens' => []],
            ],
            ['name' => 'Organic Blueberries Fresh', 'description' => 'Fresh organic blueberries packed with antioxidants.', 'description_short' => 'blueberries in clear container, fresh', 'price' => 6.99, 'sku' => 'FRT-BLU-011', 'seed' => 1011, 'nutrition_data' => ['calories' => 57, 'protein' => 1, 'carbs' => 14, 'fats' => 0.3, 'sugar' => 10, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Avocados Hass', 'description' => 'Ripe Hass avocados, perfect for guacamole and toast.', 'description_short' => 'avocado whole, green skin', 'price' => 2.49, 'sku' => 'FRT-AVO-012', 'seed' => 1012, 'nutrition_data' => ['calories' => 160, 'protein' => 2, 'carbs' => 9, 'fats' => 15, 'sugar' => 1, 'fiber' => 7, 'allergens' => []]],
            ['name' => 'Organic Baby Spinach', 'description' => 'Tender baby spinach leaves, perfect for salads and smoothies.', 'description_short' => 'spinach leaves in bag, fresh green', 'price' => 4.99, 'sku' => 'VEG-SPN-013', 'seed' => 1013, 'nutrition_data' => ['calories' => 23, 'protein' => 3, 'carbs' => 4, 'fats' => 0.4, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Sweet Potatoes', 'description' => 'Nutrient-dense sweet potatoes, great for roasting and baking.', 'description_short' => 'sweet potatoes pile, orange flesh', 'price' => 3.99, 'sku' => 'VEG-SWP-014', 'seed' => 1014, 'nutrition_data' => ['calories' => 86, 'protein' => 2, 'carbs' => 20, 'fats' => 0.1, 'sugar' => 4, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Broccoli Crowns', 'description' => 'Fresh broccoli crowns, rich in vitamins C and K.', 'description_short' => 'broccoli crown, green florets', 'price' => 3.49, 'sku' => 'VEG-BRC-015', 'seed' => 1015, 'nutrition_data' => ['calories' => 34, 'protein' => 3, 'carbs' => 7, 'fats' => 0.4, 'sugar' => 2, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Carrots Baby', 'description' => 'Sweet and crunchy baby carrots, perfect for snacking.', 'description_short' => 'baby carrots in bag, orange', 'price' => 2.99, 'sku' => 'VEG-CRT-016', 'seed' => 1016, 'nutrition_data' => ['calories' => 25, 'protein' => 1, 'carbs' => 6, 'fats' => 0.1, 'sugar' => 3, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Red Bell Peppers', 'description' => 'Sweet red bell peppers, perfect for roasting and salads.', 'description_short' => 'red bell peppers pile, vibrant', 'price' => 4.49, 'sku' => 'VEG-BLP-017', 'seed' => 1017, 'nutrition_data' => ['calories' => 31, 'protein' => 1, 'carbs' => 6, 'fats' => 0.3, 'sugar' => 4, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Cherry Tomatoes', 'description' => 'Sweet cherry tomatoes, perfect for salads and snacking.', 'description_short' => 'cherry tomatoes in container, red', 'price' => 3.99, 'sku' => 'VEG-CHT-018', 'seed' => 1018, 'nutrition_data' => ['calories' => 18, 'protein' => 1, 'carbs' => 4, 'fats' => 0.2, 'sugar' => 3, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Cucumbers English', 'description' => 'Long English cucumbers with thin skin, great for salads.', 'description_short' => 'cucumber whole, green smooth skin', 'price' => 2.49, 'sku' => 'VEG-CUC-019', 'seed' => 1019, 'nutrition_data' => ['calories' => 15, 'protein' => 1, 'carbs' => 4, 'fats' => 0.1, 'sugar' => 2, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Kale Bunch', 'description' => 'Nutrient-packed kale, perfect for smoothies and salads.', 'description_short' => 'kale bunch, dark green leaves', 'price' => 3.49, 'sku' => 'VEG-KLE-020', 'seed' => 1020, 'nutrition_data' => ['calories' => 33, 'protein' => 3, 'carbs' => 6, 'fats' => 0.6, 'sugar' => 1, 'fiber' => 4, 'allergens' => []]],
            ['name' => 'Organic Bananas', 'description' => 'Sweet organic bananas, perfect for smoothies and snacking.', 'description_short' => 'banana bunch, yellow ripe', 'price' => 1.99, 'sku' => 'FRT-BAN-021', 'seed' => 1021, 'nutrition_data' => ['calories' => 89, 'protein' => 1, 'carbs' => 23, 'fats' => 0.3, 'sugar' => 12, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Strawberries', 'description' => 'Fresh organic strawberries, sweet and juicy.', 'description_short' => 'strawberries in container, red', 'price' => 5.99, 'sku' => 'FRT-STR-022', 'seed' => 1022, 'nutrition_data' => ['calories' => 32, 'protein' => 1, 'carbs' => 8, 'fats' => 0.3, 'sugar' => 5, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Mango Fresh', 'description' => 'Sweet tropical mango, perfect for smoothies and desserts.', 'description_short' => 'mango whole, orange yellow skin', 'price' => 2.99, 'sku' => 'FRT-MNG-023', 'seed' => 1023, 'nutrition_data' => ['calories' => 60, 'protein' => 1, 'carbs' => 15, 'fats' => 0.4, 'sugar' => 14, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Pineapple Whole', 'description' => 'Fresh whole pineapple, sweet and tangy.', 'description_short' => 'pineapple whole, spiky skin', 'price' => 4.99, 'sku' => 'FRT-PIN-024', 'seed' => 1024, 'nutrition_data' => ['calories' => 50, 'protein' => 1, 'carbs' => 13, 'fats' => 0.1, 'sugar' => 10, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Watermelon Slice', 'description' => 'Fresh watermelon slice, hydrating and sweet.', 'description_short' => 'watermelon slice, red flesh', 'price' => 3.99, 'sku' => 'FRT-WTM-025', 'seed' => 1025, 'nutrition_data' => ['calories' => 30, 'protein' => 1, 'carbs' => 8, 'fats' => 0.2, 'sugar' => 6, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Oranges Navel', 'description' => 'Sweet navel oranges, perfect for juicing and snacking.', 'description_short' => 'orange whole, bright orange skin', 'price' => 1.99, 'sku' => 'FRT-ORG-026', 'seed' => 1026, 'nutrition_data' => ['calories' => 47, 'protein' => 1, 'carbs' => 12, 'fats' => 0.1, 'sugar' => 9, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Lemons', 'description' => 'Fresh lemons, perfect for cooking, baking, and beverages.', 'description_short' => 'lemon whole, yellow bright', 'price' => 1.49, 'sku' => 'FRT-LEM-027', 'seed' => 1027, 'nutrition_data' => ['calories' => 17, 'protein' => 1, 'carbs' => 5, 'fats' => 0.2, 'sugar' => 2, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Almonds Raw', 'description' => 'Raw organic almonds, perfect for snacking and baking.', 'description_short' => 'almonds pile, brown skin', 'price' => 12.99, 'sku' => 'NUT-ALM-028', 'seed' => 1028, 'nutrition_data' => ['calories' => 164, 'protein' => 6, 'carbs' => 6, 'fats' => 14, 'sugar' => 1, 'fiber' => 4, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Walnuts Halves', 'description' => 'Walnut halves, rich in omega-3 fatty acids.', 'description_short' => 'walnut halves pile, brain shape', 'price' => 11.99, 'sku' => 'NUT-WLN-029', 'seed' => 1029, 'nutrition_data' => ['calories' => 185, 'protein' => 4, 'carbs' => 4, 'fats' => 18, 'sugar' => 1, 'fiber' => 2, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Cashews Raw', 'description' => 'Raw cashews, creamy and nutritious.', 'description_short' => 'cashews pile, kidney shape', 'price' => 13.99, 'sku' => 'NUT-CSH-030', 'seed' => 1030, 'nutrition_data' => ['calories' => 157, 'protein' => 5, 'carbs' => 9, 'fats' => 12, 'sugar' => 1, 'fiber' => 1, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Pecans Halves', 'description' => 'Pecan halves, perfect for baking and snacking.', 'description_short' => 'pecan halves pile, brown', 'price' => 14.99, 'sku' => 'NUT-PEC-031', 'seed' => 1031, 'nutrition_data' => ['calories' => 196, 'protein' => 3, 'carbs' => 4, 'fats' => 20, 'sugar' => 1, 'fiber' => 3, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Pistachios Roasted', 'description' => 'Roasted pistachios with sea salt, crunchy and flavorful.', 'description_short' => 'pistachios in shell, green kernels', 'price' => 12.99, 'sku' => 'NUT-PST-032', 'seed' => 1032, 'nutrition_data' => ['calories' => 161, 'protein' => 6, 'carbs' => 8, 'fats' => 13, 'sugar' => 2, 'fiber' => 3, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Sunflower Seeds', 'description' => 'Raw sunflower seeds, perfect for snacking and baking.', 'description_short' => 'sunflower seeds pile, grey shells', 'price' => 6.99, 'sku' => 'SED-SNF-033', 'seed' => 1033, 'nutrition_data' => ['calories' => 164, 'protein' => 6, 'carbs' => 7, 'fats' => 14, 'sugar' => 1, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Pumpkin Seeds', 'description' => 'Pumpkin seeds, rich in zinc and magnesium.', 'description_short' => 'pumpkin seeds pile, green', 'price' => 7.99, 'sku' => 'SED-PMP-034', 'seed' => 1034, 'nutrition_data' => ['calories' => 151, 'protein' => 7, 'carbs' => 5, 'fats' => 13, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Flaxseeds Ground', 'description' => 'Ground flaxseeds, rich in omega-3 and fiber.', 'description_short' => 'flaxseed powder in jar, brown', 'price' => 6.99, 'sku' => 'SED-FLX-035', 'seed' => 1035, 'nutrition_data' => ['calories' => 150, 'protein' => 5, 'carbs' => 8, 'fats' => 12, 'sugar' => 0, 'fiber' => 8, 'allergens' => []]],
            ['name' => 'Organic Hemp Seeds', 'description' => 'Hemp seeds, complete protein source with all amino acids.', 'description_short' => 'hemp seeds pile, greenish', 'price' => 9.99, 'sku' => 'SED-HMP-036', 'seed' => 1036, 'nutrition_data' => ['calories' => 166, 'protein' => 10, 'carbs' => 3, 'fats' => 13, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Brown Rice', 'description' => 'Whole grain brown rice, nutty flavor and chewy texture.', 'description_short' => 'brown rice in bag, grains visible', 'price' => 5.99, 'sku' => 'GRN-BRN-037', 'seed' => 1037, 'nutrition_data' => ['calories' => 111, 'protein' => 3, 'carbs' => 23, 'fats' => 1, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Wild Rice', 'description' => 'Wild rice blend, earthy flavor and firm texture.', 'description_short' => 'wild rice grains, dark brown', 'price' => 8.99, 'sku' => 'GRN-WLD-038', 'seed' => 1038, 'nutrition_data' => ['calories' => 101, 'protein' => 4, 'carbs' => 21, 'fats' => 0.4, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Barley Pearled', 'description' => 'Pearled barley, perfect for soups and salads.', 'description_short' => 'barley grains pile, light brown', 'price' => 6.49, 'sku' => 'GRN-BRL-039', 'seed' => 1039, 'nutrition_data' => ['calories' => 123, 'protein' => 2, 'carbs' => 28, 'fats' => 0.4, 'sugar' => 0, 'fiber' => 4, 'allergens' => ['gluten']]],
            ['name' => 'Organic Buckwheat Groats', 'description' => 'Buckwheat groats, gluten-free and nutrient-dense.', 'description_short' => 'buckwheat groats pile, triangular', 'price' => 7.49, 'sku' => 'GRN-BKW-040', 'seed' => 1040, 'nutrition_data' => ['calories' => 155, 'protein' => 6, 'carbs' => 34, 'fats' => 1, 'sugar' => 0, 'fiber' => 5, 'allergens' => []]],
            ['name' => 'Organic Millet Hulled', 'description' => 'Hulled millet, ancient grain with mild flavor.', 'description_short' => 'millet grains pile, yellow', 'price' => 6.99, 'sku' => 'GRN-MLT-041', 'seed' => 1041, 'nutrition_data' => ['calories' => 119, 'protein' => 4, 'carbs' => 23, 'fats' => 1, 'sugar' => 0, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Amaranth Grain', 'description' => 'Amaranth grain, protein-rich ancient superfood.', 'description_short' => 'amaranth grains pile, tiny golden', 'price' => 8.49, 'sku' => 'GRN-AMR-042', 'seed' => 1042, 'nutrition_data' => ['calories' => 102, 'protein' => 4, 'carbs' => 19, 'fats' => 2, 'sugar' => 0, 'fiber' => 2, 'allergens' => []]],
            ['name' => 'Organic Whole Wheat Flour', 'description' => 'Stone-ground whole wheat flour, perfect for baking.', 'description_short' => 'wheat flour in bag, off-white', 'price' => 4.99, 'sku' => 'GRN-WHT-043', 'seed' => 1043, 'nutrition_data' => ['calories' => 91, 'protein' => 3, 'carbs' => 19, 'fats' => 0.5, 'sugar' => 0, 'fiber' => 3, 'allergens' => ['gluten']]],
            ['name' => 'Organic Almond Flour', 'description' => 'Blanched almond flour, perfect for gluten-free baking.', 'description_short' => 'almond flour in jar, fine white', 'price' => 11.99, 'sku' => 'GRN-ALF-044', 'seed' => 1044, 'nutrition_data' => ['calories' => 163, 'protein' => 6, 'carbs' => 6, 'fats' => 14, 'sugar' => 1, 'fiber' => 3, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Coconut Flour', 'description' => 'Coconut flour, high fiber gluten-free baking alternative.', 'description_short' => 'coconut flour pile, white fine', 'price' => 8.99, 'sku' => 'GRN-COF-045', 'seed' => 1045, 'nutrition_data' => ['calories' => 120, 'protein' => 4, 'carbs' => 21, 'fats' => 4, 'sugar' => 6, 'fiber' => 10, 'allergens' => []]],
            ['name' => 'Organic Sourdough Bread', 'description' => 'Fresh sourdough bread, tangy flavor and chewy crust.', 'description_short' => 'sourdough loaf, rustic crust', 'price' => 6.99, 'sku' => 'BKR-SRD-046', 'seed' => 1046, 'nutrition_data' => ['calories' => 196, 'protein' => 7, 'carbs' => 37, 'fats' => 2, 'sugar' => 1, 'fiber' => 2, 'allergens' => ['gluten']]],
            ['name' => 'Organic Multigrain Bread', 'description' => 'Multigrain bread with seeds and grains for extra nutrition.', 'description_short' => 'multigrain bread slice, seeded', 'price' => 5.99, 'sku' => 'BKR-MTG-047', 'seed' => 1047, 'nutrition_data' => ['calories' => 180, 'protein' => 6, 'carbs' => 34, 'fats' => 3, 'sugar' => 4, 'fiber' => 5, 'allergens' => ['gluten']]],
            ['name' => 'Organic Gluten-Free Bread', 'description' => 'Soft gluten-free bread, perfect for sandwiches.', 'description_short' => 'gluten free bread loaf, soft', 'price' => 7.49, 'sku' => 'BKR-GFB-048', 'seed' => 1048, 'nutrition_data' => ['calories' => 170, 'protein' => 4, 'carbs' => 32, 'fats' => 4, 'sugar' => 3, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Granola Mixed Berry', 'description' => 'Crunchy granola with mixed berries and nuts.', 'description_short' => 'granola in bowl, colorful', 'price' => 8.99, 'sku' => 'BKR-GRN-049', 'seed' => 1049, 'nutrition_data' => ['calories' => 210, 'protein' => 5, 'carbs' => 28, 'fats' => 9, 'sugar' => 12, 'fiber' => 3, 'allergens' => ['oats', 'tree nuts']]],
            ['name' => 'Organic Muesli Original', 'description' => 'Traditional muesli with oats, nuts, and dried fruits.', 'description_short' => 'muesli mix in bowl, natural', 'price' => 7.49, 'sku' => 'BKR-MSL-050', 'seed' => 1050, 'nutrition_data' => ['calories' => 180, 'protein' => 5, 'carbs' => 30, 'fats' => 5, 'sugar' => 10, 'fiber' => 4, 'allergens' => ['oats', 'tree nuts']]],
            ['name' => 'Organic Cornflakes', 'description' => 'Classic cornflakes, crispy and golden.', 'description_short' => 'cornflakes in bowl, golden yellow', 'price' => 4.99, 'sku' => 'BKR-CRF-051', 'seed' => 1051, 'nutrition_data' => ['calories' => 100, 'protein' => 2, 'carbs' => 24, 'fats' => 0.2, 'sugar' => 3, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Rice Noodles', 'description' => 'Rice noodles, gluten-free alternative to wheat pasta.', 'description_short' => 'rice noodles bundle, white', 'price' => 5.49, 'sku' => 'PST-RCN-052', 'seed' => 1052, 'nutrition_data' => ['calories' => 108, 'protein' => 1, 'carbs' => 25, 'fats' => 0.2, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Whole Wheat Pasta', 'description' => 'Whole wheat penne pasta, hearty and nutritious.', 'description_short' => 'pasta penne whole wheat, brown', 'price' => 4.99, 'sku' => 'PST-WWP-053', 'seed' => 1053, 'nutrition_data' => ['calories' => 174, 'protein' => 7, 'carbs' => 37, 'fats' => 1, 'sugar' => 2, 'fiber' => 6, 'allergens' => ['gluten']]],
            ['name' => 'Organic Quinoa Pasta', 'description' => 'Quinoa pasta, gluten-free and protein-rich.', 'description_short' => 'quinoa pasta spaghetti, golden', 'price' => 6.99, 'sku' => 'PST-QUP-054', 'seed' => 1054, 'nutrition_data' => ['calories' => 180, 'protein' => 5, 'carbs' => 36, 'fats' => 2, 'sugar' => 1, 'fiber' => 3, 'allergens' => []]],
            ['name' => 'Organic Chickpeas Canned', 'description' => 'Organic chickpeas, perfect for hummus and salads.', 'description_short' => 'chickpeas in can, beige round', 'price' => 2.99, 'sku' => 'LGM-CHK-055', 'seed' => 1055, 'nutrition_data' => ['calories' => 134, 'protein' => 7, 'carbs' => 22, 'fats' => 2, 'sugar' => 4, 'fiber' => 4, 'allergens' => []]],
            ['name' => 'Organic Black Beans Canned', 'description' => 'Organic black beans, rich in fiber and protein.', 'description_short' => 'black beans in can, dark', 'price' => 2.99, 'sku' => 'LGM-BLK-056', 'seed' => 1056, 'nutrition_data' => ['calories' => 132, 'protein' => 9, 'carbs' => 24, 'fats' => 0.5, 'sugar' => 0, 'fiber' => 8, 'allergens' => []]],
            ['name' => 'Organic Lentils Red', 'description' => 'Red lentils, quick cooking and perfect for soups.', 'description_short' => 'red lentils pile, orange red', 'price' => 4.49, 'sku' => 'LGM-RDL-057', 'seed' => 1057, 'nutrition_data' => ['calories' => 116, 'protein' => 9, 'carbs' => 20, 'fats' => 0.4, 'sugar' => 2, 'fiber' => 8, 'allergens' => []]],
            ['name' => 'Organic Edamame Frozen', 'description' => 'Frozen edamame, perfect snack or salad topping.', 'description_short' => 'edamame in pod, green', 'price' => 5.99, 'sku' => 'LGM-EDM-058', 'seed' => 1058, 'nutrition_data' => ['calories' => 121, 'protein' => 12, 'carbs' => 10, 'fats' => 5, 'sugar' => 2, 'fiber' => 4, 'allergens' => ['soy']]],
            ['name' => 'Organic Tofu Firm', 'description' => 'Firm organic tofu, versatile protein source.', 'description_short' => 'tofu block, white firm', 'price' => 3.99, 'sku' => 'LGM-TOF-059', 'seed' => 1059, 'nutrition_data' => ['calories' => 144, 'protein' => 17, 'carbs' => 3, 'fats' => 9, 'sugar' => 0, 'fiber' => 2, 'allergens' => ['soy']]],
            ['name' => 'Organic Tempeh Original', 'description' => 'Fermented tempeh, nutty flavor and high protein.', 'description_short' => 'tempeh block, firm textured', 'price' => 4.99, 'sku' => 'LGM-TMP-060', 'seed' => 1060, 'nutrition_data' => ['calories' => 193, 'protein' => 18, 'carbs' => 9, 'fats' => 11, 'sugar' => 0, 'fiber' => 5, 'allergens' => ['soy']]],
            ['name' => 'Organic Apple Cider Vinegar', 'description' => 'Raw unfiltered apple cider vinegar with the mother.', 'description_short' => 'apple cider vinegar bottle, amber', 'price' => 6.99, 'sku' => 'CND-ACV-061', 'seed' => 1061, 'nutrition_data' => ['calories' => 3, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Balsamic Vinegar', 'description' => 'Aged balsamic vinegar, rich and tangy.', 'description_short' => 'balsamic vinegar bottle, dark', 'price' => 9.99, 'sku' => 'CND-BSV-062', 'seed' => 1062, 'nutrition_data' => ['calories' => 14, 'protein' => 0, 'carbs' => 3, 'fats' => 0, 'sugar' => 3, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Maple Syrup Pure', 'description' => 'Pure maple syrup, natural sweetener for pancakes.', 'description_short' => 'maple syrup bottle, amber glass', 'price' => 12.99, 'sku' => 'CND-MPS-063', 'seed' => 1063, 'nutrition_data' => ['calories' => 52, 'protein' => 0, 'carbs' => 13, 'fats' => 0, 'sugar' => 12, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Agave Nectar', 'description' => 'Organic agave nectar, low glycemic sweetener.', 'description_short' => 'agave nectar bottle, golden', 'price' => 8.99, 'sku' => 'CND-AGV-064', 'seed' => 1064, 'nutrition_data' => ['calories' => 60, 'protein' => 0, 'carbs' => 16, 'fats' => 0, 'sugar' => 16, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Coconut Aminos', 'description' => 'Coconut aminos, soy-free alternative to soy sauce.', 'description_short' => 'coconut aminos bottle, dark', 'price' => 7.99, 'sku' => 'CND-CAM-065', 'seed' => 1065, 'nutrition_data' => ['calories' => 10, 'protein' => 1, 'carbs' => 2, 'fats' => 0, 'sugar' => 1, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Sesame Oil Toasted', 'description' => 'Toasted sesame oil, rich nutty flavor for Asian cooking.', 'description_short' => 'sesame oil bottle, dark amber', 'price' => 8.49, 'sku' => 'OIL-SSM-066', 'seed' => 1066, 'nutrition_data' => ['calories' => 120, 'protein' => 0, 'carbs' => 0, 'fats' => 14, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['sesame']]],
            ['name' => 'Organic Avocado Oil Spray', 'description' => 'Avocado oil cooking spray, non-stick and healthy.', 'description_short' => 'avocado oil spray can, green', 'price' => 6.99, 'sku' => 'OIL-AVS-067', 'seed' => 1067, 'nutrition_data' => ['calories' => 120, 'protein' => 0, 'carbs' => 0, 'fats' => 14, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Ghee Clarified', 'description' => 'Clarified butter ghee, lactose-free and shelf-stable.', 'description_short' => 'ghee jar, golden clarified butter', 'price' => 11.99, 'sku' => 'OIL-GHE-068', 'seed' => 1068, 'nutrition_data' => ['calories' => 120, 'protein' => 0, 'carbs' => 0, 'fats' => 14, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Tahini Sesame Paste', 'description' => 'Smooth tahini paste, perfect for hummus and dressings.', 'description_short' => 'tahini jar, creamy beige', 'price' => 9.49, 'sku' => 'CND-THN-069', 'seed' => 1069, 'nutrition_data' => ['calories' => 178, 'protein' => 5, 'carbs' => 7, 'fats' => 16, 'sugar' => 1, 'fiber' => 3, 'allergens' => ['sesame']]],
            ['name' => 'Organic Almond Butter', 'description' => 'Smooth almond butter, creamy and protein-rich.', 'description_short' => 'almond butter jar, brown creamy', 'price' => 10.99, 'sku' => 'NUT-ABT-070', 'seed' => 1070, 'nutrition_data' => ['calories' => 196, 'protein' => 7, 'carbs' => 7, 'fats' => 18, 'sugar' => 2, 'fiber' => 3, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Cashew Butter', 'description' => 'Creamy cashew butter, mild and sweet flavor.', 'description_short' => 'cashew butter jar, light tan', 'price' => 11.99, 'sku' => 'NUT-CBT-071', 'seed' => 1071, 'nutrition_data' => ['calories' => 180, 'protein' => 5, 'carbs' => 9, 'fats' => 15, 'sugar' => 3, 'fiber' => 1, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Green Tea Matcha', 'description' => 'Ceremonial grade matcha powder, vibrant green.', 'description_short' => 'matcha powder tin, bright green', 'price' => 14.99, 'sku' => 'BEV-MTC-072', 'seed' => 1072, 'nutrition_data' => ['calories' => 5, 'protein' => 0, 'carbs' => 1, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Herbal Tea Chamomile', 'description' => 'Chamomile herbal tea, calming and soothing.', 'description_short' => 'chamomile tea box, yellow flowers', 'price' => 6.99, 'sku' => 'BEV-CHM-073', 'seed' => 1073, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Kombucha Ginger', 'description' => 'Fermented kombucha with ginger, probiotic-rich.', 'description_short' => 'kombucha bottle, amber liquid', 'price' => 5.99, 'sku' => 'BEV-KMB-074', 'seed' => 1074, 'nutrition_data' => ['calories' => 30, 'protein' => 0, 'carbs' => 7, 'fats' => 0, 'sugar' => 6, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Sparkling Water Lemon', 'description' => 'Sparkling water with natural lemon flavor.', 'description_short' => 'sparkling water can, lemon design', 'price' => 2.49, 'sku' => 'BEV-SPK-075', 'seed' => 1075, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Plant Protein Vanilla', 'description' => 'Vanilla flavored plant protein powder, vegan.', 'description_short' => 'protein tub, vanilla label', 'price' => 34.99, 'sku' => 'SUP-PPV-076', 'seed' => 1076, 'nutrition_data' => ['calories' => 120, 'protein' => 24, 'carbs' => 3, 'fats' => 2, 'sugar' => 1, 'fiber' => 1, 'allergens' => ['soy']]],
            ['name' => 'Organic Omega-3 Fish Oil', 'description' => 'Omega-3 fish oil capsules, heart and brain health.', 'description_short' => 'fish oil bottle, yellow capsules', 'price' => 19.99, 'sku' => 'SUP-OM3-077', 'seed' => 1077, 'nutrition_data' => ['calories' => 10, 'protein' => 0, 'carbs' => 0, 'fats' => 1, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['fish']]],
            ['name' => 'Organic Vitamin D3 Drops', 'description' => 'Vitamin D3 liquid drops, immune support.', 'description_short' => 'vitamin d3 dropper bottle, small', 'price' => 14.99, 'sku' => 'SUP-VTD-078', 'seed' => 1078, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Probiotic Capsules', 'description' => 'Multi-strain probiotic capsules, gut health support.', 'description_short' => 'probiotic bottle, white capsules', 'price' => 24.99, 'sku' => 'SUP-PRB-079', 'seed' => 1079, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Magnesium Glycinate', 'description' => 'Magnesium glycinate for sleep and muscle relaxation.', 'description_short' => 'magnesium bottle, brown tablets', 'price' => 16.99, 'sku' => 'SUP-MAG-080', 'seed' => 1080, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Collagen Peptides', 'description' => 'Collagen peptides powder, skin and joint support.', 'description_short' => 'collagen tub, white powder', 'price' => 29.99, 'sku' => 'SUP-COL-081', 'seed' => 1081, 'nutrition_data' => ['calories' => 45, 'protein' => 11, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Turmeric Curcumin', 'description' => 'Turmeric curcumin capsules with black pepper.', 'description_short' => 'turmeric bottle, orange capsules', 'price' => 18.99, 'sku' => 'SUP-TRM-082', 'seed' => 1082, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Ashwagandha Root', 'description' => 'Ashwagandha root extract, stress and anxiety support.', 'description_short' => 'ashwagandha bottle, brown capsules', 'price' => 17.99, 'sku' => 'SUP-ASH-083', 'seed' => 1083, 'nutrition_data' => ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0, 'sugar' => 0, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Elderberry Syrup', 'description' => 'Elderberry syrup, immune system booster.', 'description_short' => 'elderberry syrup bottle, dark purple', 'price' => 15.99, 'sku' => 'SUP-ELD-084', 'seed' => 1084, 'nutrition_data' => ['calories' => 20, 'protein' => 0, 'carbs' => 5, 'fats' => 0, 'sugar' => 4, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Spirulina Powder', 'description' => 'Spirulina powder, blue-green algae superfood.', 'description_short' => 'spirulina jar, dark green powder', 'price' => 19.99, 'sku' => 'SUP-SPR-085', 'seed' => 1085, 'nutrition_data' => ['calories' => 20, 'protein' => 4, 'carbs' => 2, 'fats' => 0, 'sugar' => 0, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Maca Powder', 'description' => 'Maca root powder, energy and hormone balance.', 'description_short' => 'maca powder jar, beige powder', 'price' => 16.99, 'sku' => 'SUP-MAC-086', 'seed' => 1086, 'nutrition_data' => ['calories' => 25, 'protein' => 1, 'carbs' => 5, 'fats' => 0, 'sugar' => 2, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Chlorella Tablets', 'description' => 'Chlorella tablets, detox and nutrient support.', 'description_short' => 'chlorella bottle, green tablets', 'price' => 18.99, 'sku' => 'SUP-CHL-087', 'seed' => 1087, 'nutrition_data' => ['calories' => 15, 'protein' => 3, 'carbs' => 1, 'fats' => 0, 'sugar' => 0, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Coconut Milk Canned', 'description' => 'Full-fat coconut milk, creamy for cooking and curries.', 'description_short' => 'coconut milk can, white label', 'price' => 3.99, 'sku' => 'DAI-COM-088', 'seed' => 1088, 'nutrition_data' => ['calories' => 180, 'protein' => 2, 'carbs' => 3, 'fats' => 18, 'sugar' => 1, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Soy Milk Unsweetened', 'description' => 'Unsweetened soy milk, high protein dairy alternative.', 'description_short' => 'soy milk carton, white', 'price' => 3.99, 'sku' => 'DAI-SYM-089', 'seed' => 1089, 'nutrition_data' => ['calories' => 80, 'protein' => 7, 'carbs' => 4, 'fats' => 4, 'sugar' => 1, 'fiber' => 1, 'allergens' => ['soy']]],
            ['name' => 'Organic Rice Milk Original', 'description' => 'Rice milk, light and naturally sweet dairy alternative.', 'description_short' => 'rice milk carton, light', 'price' => 4.49, 'sku' => 'DAI-RCM-090', 'seed' => 1090, 'nutrition_data' => ['calories' => 120, 'protein' => 1, 'carbs' => 23, 'fats' => 2, 'sugar' => 10, 'fiber' => 0, 'allergens' => []]],
            ['name' => 'Organic Cashew Milk', 'description' => 'Creamy cashew milk, dairy-free and smooth.', 'description_short' => 'cashew milk carton, beige', 'price' => 4.99, 'sku' => 'DAI-CSM-091', 'seed' => 1091, 'nutrition_data' => ['calories' => 25, 'protein' => 1, 'carbs' => 1, 'fats' => 2, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['tree nuts']]],
            ['name' => 'Organic Hemp Milk', 'description' => 'Hemp milk, omega-rich dairy alternative.', 'description_short' => 'hemp milk carton, green design', 'price' => 5.49, 'sku' => 'DAI-HMP-092', 'seed' => 1092, 'nutrition_data' => ['calories' => 60, 'protein' => 2, 'carbs' => 1, 'fats' => 5, 'sugar' => 0, 'fiber' => 1, 'allergens' => []]],
            ['name' => 'Organic Parmesan Cheese', 'description' => 'Aged parmesan cheese, sharp and savory.', 'description_short' => 'parmesan wedge, hard cheese', 'price' => 8.99, 'sku' => 'DAI-PRM-093', 'seed' => 1093, 'nutrition_data' => ['calories' => 110, 'protein' => 10, 'carbs' => 0, 'fats' => 7, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Mozzarella Fresh', 'description' => 'Fresh mozzarella, soft and creamy.', 'description_short' => 'mozzarella ball, white fresh', 'price' => 6.99, 'sku' => 'DAI-MOZ-094', 'seed' => 1094, 'nutrition_data' => ['calories' => 85, 'protein' => 6, 'carbs' => 1, 'fats' => 6, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Feta Cheese', 'description' => 'Crumbled feta cheese, tangy and salty.', 'description_short' => 'feta cheese crumbles, white', 'price' => 5.99, 'sku' => 'DAI-FET-095', 'seed' => 1095, 'nutrition_data' => ['calories' => 75, 'protein' => 4, 'carbs' => 1, 'fats' => 6, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Ricotta Cheese', 'description' => 'Creamy ricotta cheese, perfect for pasta and desserts.', 'description_short' => 'ricotta tub, white creamy', 'price' => 5.49, 'sku' => 'DAI-RIC-096', 'seed' => 1096, 'nutrition_data' => ['calories' => 43, 'protein' => 3, 'carbs' => 1, 'fats' => 3, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Cottage Cheese', 'description' => 'Cottage cheese, high protein and low fat.', 'description_short' => 'cottage cheese tub, curds visible', 'price' => 4.99, 'sku' => 'DAI-CTG-097', 'seed' => 1097, 'nutrition_data' => ['calories' => 72, 'protein' => 13, 'carbs' => 3, 'fats' => 1, 'sugar' => 3, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Cream Cheese', 'description' => 'Cream cheese, smooth and spreadable.', 'description_short' => 'cream cheese tub, white', 'price' => 4.49, 'sku' => 'DAI-CRM-098', 'seed' => 1098, 'nutrition_data' => ['calories' => 100, 'protein' => 2, 'carbs' => 1, 'fats' => 10, 'sugar' => 1, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Butter Unsalted', 'description' => 'Unsalted butter, perfect for baking and cooking.', 'description_short' => 'butter block, yellow', 'price' => 5.99, 'sku' => 'DAI-BTR-099', 'seed' => 1099, 'nutrition_data' => ['calories' => 100, 'protein' => 0, 'carbs' => 0, 'fats' => 11, 'sugar' => 0, 'fiber' => 0, 'allergens' => ['dairy']]],
            ['name' => 'Organic Whipped Cream', 'description' => 'Organic whipped cream, light and fluffy.', 'description_short' => 'whipped cream can, white', 'price' => 4.99, 'sku' => 'DAI-WHC-100', 'seed' => 1100, 'nutrition_data' => ['calories' => 50, 'protein' => 0, 'carbs' => 1, 'fats' => 5, 'sugar' => 1, 'fiber' => 0, 'allergens' => ['dairy']]],
        ];
    }
}