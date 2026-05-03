<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(3),
            'price' => fake()->randomFloat(2, 1, 50),
            'compare_at_price' => fake()->boolean(30) ? fake()->randomFloat(2, 5, 60) : null,
            'image_url' => fake()->imageUrl(400, 400, 'food'),
            'category_id' => Category::factory(),
            'vendor_id' => User::factory()->vendor(),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'min_stock_threshold' => 10,
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
            'nutrition_data' => $this->generateNutritionData(),
            'sku' => strtoupper(Str::random(10)),
            'weight_kg' => fake()->randomFloat(3, 0.1, 5.0),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(0, 5),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    private function generateNutritionData(): array
    {
        return [
            'calories' => fake()->numberBetween(50, 800),
            'protein' => fake()->randomFloat(1, 0, 50),
            'carbs' => fake()->randomFloat(1, 0, 100),
            'fats' => fake()->randomFloat(1, 0, 40),
            'sugar' => fake()->randomFloat(1, 0, 30),
            'fiber' => fake()->randomFloat(1, 0, 15),
            'allergens' => fake()->randomElements([
                'dairy',
                'nuts',
                'soy',
                'gluten',
                'eggs',
                'shellfish',
            ], fake()->numberBetween(0, 3)),
        ];
    }
}
