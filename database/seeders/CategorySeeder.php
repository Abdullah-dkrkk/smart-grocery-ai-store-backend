<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Dairy & Eggs',
                'slug' => 'dairy-eggs',
                'description' => 'Milk, cheese, eggs, and other dairy products',
            ],
            [
                'name' => 'Fresh Produce',
                'slug' => 'fresh-produce',
                'description' => 'Fresh fruits and vegetables',
                'children' => ['Fruits', 'Vegetables', 'Organic Produce'],
            ],
            [
                'name' => 'Meat & Seafood',
                'slug' => 'meat-seafood',
                'description' => 'Fresh meat, poultry, and seafood',
            ],
            [
                'name' => 'Bakery',
                'slug' => 'bakery',
                'description' => 'Fresh bread, pastries, and baked goods',
            ],
            [
                'name' => 'Health & Wellness',
                'slug' => 'health-wellness',
                'description' => 'Supplements, vitamins, and health products',
                'children' => ['Vitamins', 'Protein Supplements', 'Herbal Supplements'],
            ],
            [
                'name' => 'Pantry Staples',
                'slug' => 'pantry-staples',
                'description' => 'Rice, pasta, canned goods, and cooking essentials',
            ],
            [
                'name' => 'Snacks',
                'slug' => 'snacks',
                'description' => 'Healthy snacks, chips, crackers, and more',
            ],
            [
                'name' => 'Beverages',
                'slug' => 'beverages',
                'description' => 'Juices, teas, coffees, and other drinks',
                'children' => ['Juices & Smoothies', 'Tea & Coffee', 'Plant-based Milk'],
            ],
            [
                'name' => 'Frozen Foods',
                'slug' => 'frozen-foods',
                'description' => 'Frozen meals, vegetables, and desserts',
            ],
            [
                'name' => 'Specialty Diet',
                'slug' => 'specialty-diet',
                'description' => 'Gluten-free, vegan, keto, and other dietary-specific products',
                'children' => ['Gluten-Free', 'Vegan', 'Keto', 'Paleo'],
            ],
        ];

        foreach ($categories as $category) {
            $children = $category['children'] ?? [];
            unset($category['children']);

            $parent = Category::firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => array_search($category['name'], array_column($categories, 'name')),
                    'is_active' => true,
                ]
            );

            foreach ($children as $childName) {
                $childSlug = \Illuminate\Support\Str::slug($childName) . '-' . $parent->id;
                Category::firstOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name' => $childName,
                        'description' => "Subcategory of {$parent->name}",
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
