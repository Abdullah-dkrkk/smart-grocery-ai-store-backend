<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 200);
        $tax = round($subtotal * 0.05, 2);
        $shipping = fake()->randomElement([0, 5.99, 9.99, 14.99]);
        $discount = fake()->boolean(20) ? fake()->randomFloat(2, 5, 30) : 0;
        $total = $subtotal + $tax + $shipping - $discount;

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . strtoupper(fake()->unique()->numerify('########')),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_cost' => $shipping,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'payment_method' => fake()->randomElement(['card', 'cod', 'wallet']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'shipping_address' => fake()->address(),
            'billing_address' => fake()->boolean(70) ? fake()->address() : null,
            'shipping_phone' => fake()->phoneNumber(),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipped_at' => now()->subDays(2),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now()->subDays(2),
            'payment_status' => 'paid',
            'paid_at' => now()->subDays(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now()->subDays(1),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
