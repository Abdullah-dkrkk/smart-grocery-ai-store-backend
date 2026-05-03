<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = User::where('role', 'vendor')->get();
        $categories = Category::whereNull('parent_id')->get();

        if ($vendors->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $products = $this->getSampleProducts();

        foreach ($products as $productData) {
            $category = $categories->random();
            $vendor = $vendors->random();
            $slug = \Illuminate\Support\Str::slug($productData['name']);

            Product::firstOrCreate(
                ['slug' => $slug],
                array_merge($productData, [
                    'category_id' => $category->id,
                    'vendor_id' => $vendor->id,
                ])
            );
        }

        Product::factory()->count(20)->create();
    }

    private function getSampleProducts(): array
    {
        return [
            [
                'name' => 'Organic Almond Milk Unsweetened',
                'description' => 'Fresh organic almond milk with no added sugar. Perfect for smoothies, coffee, and cereals.',
                'price' => 4.99,
                'compare_at_price' => 6.49,
                'stock_quantity' => 150,
                'is_active' => true,
                'is_featured' => true,
                'nutrition_data' => [
                    'calories' => 30,
                    'protein' => 1,
                    'carbs' => 1,
                    'fats' => 2.5,
                    'sugar' => 0,
                    'fiber' => 0,
                    'allergens' => ['tree nuts'],
                ],
                'sku' => 'ORG-ALM-001',
                'weight_kg' => 1.0,
            ],
            [
                'name' => 'Grass-Fed Whey Protein Isolate',
                'description' => 'Premium grass-fed whey protein isolate for muscle building and recovery. 25g protein per serving.',
                'price' => 39.99,
                'compare_at_price' => 49.99,
                'stock_quantity' => 75,
                'is_active' => true,
                'is_featured' => true,
                'nutrition_data' => [
                    'calories' => 120,
                    'protein' => 25,
                    'carbs' => 2,
                    'fats' => 1,
                    'sugar' => 1,
                    'fiber' => 0,
                    'allergens' => ['dairy'],
                ],
                'sku' => 'PRO-WHY-001',
                'weight_kg' => 0.5,
            ],
            [
                'name' => 'Organic Quinoa Grain',
                'description' => 'Premium organic quinoa. High in protein, fiber, and essential amino acids. Gluten-free.',
                'price' => 8.99,
                'stock_quantity' => 200,
                'is_active' => true,
                'is_featured' => false,
                'nutrition_data' => [
                    'calories' => 222,
                    'protein' => 8,
                    'carbs' => 39,
                    'fats' => 3.5,
                    'sugar' => 0,
                    'fiber' => 5,
                    'allergens' => [],
                ],
                'sku' => 'GRA-QUI-001',
                'weight_kg' => 0.5,
            ],
            [
                'name' => 'Wild Caught Salmon Fillet',
                'description' => 'Fresh wild-caught Alaskan salmon. Rich in omega-3 fatty acids and high-quality protein.',
                'price' => 14.99,
                'stock_quantity' => 45,
                'is_active' => true,
                'is_featured' => true,
                'nutrition_data' => [
                    'calories' => 208,
                    'protein' => 20,
                    'carbs' => 0,
                    'fats' => 13,
                    'sugar' => 0,
                    'fiber' => 0,
                    'allergens' => ['fish'],
                ],
                'sku' => 'SEA-SAL-001',
                'weight_kg' => 0.3,
            ],
            [
                'name' => 'Organic Avocado Oil',
                'description' => 'Cold-pressed organic avocado oil. Perfect for cooking, dressings, and skincare.',
                'price' => 12.99,
                'stock_quantity' => 100,
                'is_active' => true,
                'is_featured' => false,
                'nutrition_data' => [
                    'calories' => 120,
                    'protein' => 0,
                    'carbs' => 0,
                    'fats' => 14,
                    'sugar' => 0,
                    'fiber' => 0,
                    'allergens' => [],
                ],
                'sku' => 'OIL-AVO-001',
                'weight_kg' => 0.5,
            ],
            [
                'name' => 'Greek Yogurt Plain Non-Fat',
                'description' => 'Creamy Greek-style yogurt with no added sugar. High in protein and probiotics.',
                'price' => 3.49,
                'stock_quantity' => 300,
                'is_active' => true,
                'is_featured' => false,
                'nutrition_data' => [
                    'calories' => 100,
                    'protein' => 17,
                    'carbs' => 6,
                    'fats' => 0.5,
                    'sugar' => 4,
                    'fiber' => 0,
                    'allergens' => ['dairy'],
                ],
                'sku' => 'DAI-YOG-001',
                'weight_kg' => 0.5,
            ],
            [
                'name' => 'Organic Chia Seeds',
                'description' => 'Premium organic chia seeds packed with omega-3, fiber, and protein.',
                'price' => 9.99,
                'compare_at_price' => 12.99,
                'stock_quantity' => 180,
                'is_active' => true,
                'is_featured' => true,
                'nutrition_data' => [
                    'calories' => 138,
                    'protein' => 5,
                    'carbs' => 12,
                    'fats' => 9,
                    'sugar' => 0,
                    'fiber' => 10,
                    'allergens' => [],
                ],
                'sku' => 'SEE-CHI-001',
                'weight_kg' => 0.4,
            ],
            [
                'name' => 'Gluten-Free Oat Bread',
                'description' => 'Freshly baked gluten-free oat bread. Soft, nutritious, and perfect for toast.',
                'price' => 5.49,
                'stock_quantity' => 60,
                'is_active' => true,
                'is_featured' => false,
                'nutrition_data' => [
                    'calories' => 90,
                    'protein' => 3,
                    'carbs' => 16,
                    'fats' => 1.5,
                    'sugar' => 2,
                    'fiber' => 2,
                    'allergens' => ['oats'],
                ],
                'sku' => 'BAK-OAT-001',
                'weight_kg' => 0.4,
            ],
        ];
    }
}
