<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'message' => fake()->sentence(),
            'response' => fake()->paragraph(2),
            'type' => fake()->randomElement(['text', 'image']),
            'context' => fake()->randomElement([
                'product_recommendation',
                'nutrition_query',
                'diet_plan',
                'product_search',
                'health_advice',
            ]),
            'metadata' => [
                'model' => fake()->randomElement(['gpt-4', 'gpt-3.5-turbo', 'gemini-pro']),
                'tokens_used' => fake()->numberBetween(100, 2000),
            ],
            'image_url' => fake()->boolean(30) ? fake()->imageUrl() : null,
            'response_time_ms' => fake()->randomFloat(0, 200, 3000),
        ];
    }

    public function imageQuery(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'image_url' => fake()->imageUrl(),
        ]);
    }

    public function textQuery(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'image_url' => null,
        ]);
    }
}
