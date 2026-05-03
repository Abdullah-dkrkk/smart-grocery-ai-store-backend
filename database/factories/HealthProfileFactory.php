<?php

namespace Database\Factories;

use App\Models\HealthProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthProfile>
 */
class HealthProfileFactory extends Factory
{
    public function definition(): array
    {
        $weight = fake()->randomFloat(1, 45, 120);
        $height = fake()->randomFloat(1, 150, 200);
        $bmi = round($weight / (($height / 100) ** 2), 1);

        return [
            'user_id' => User::factory(),
            'age' => fake()->numberBetween(18, 70),
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
            'goals' => fake()->randomElement([
                'Weight loss',
                'Muscle gain',
                'Maintain weight',
                'Improve energy',
                'Heart health',
            ]),
            'allergies' => fake()->randomElements([
                'Peanuts',
                'Shellfish',
                'Dairy',
                'Gluten',
                'Eggs',
                'Soy',
                'Tree nuts',
            ], fake()->numberBetween(0, 3)),
            'dietary_type' => fake()->randomElement([
                'Vegetarian',
                'Vegan',
                'Keto',
                'Paleo',
                'Gluten-free',
                null,
            ]),
            'activity_level' => fake()->randomElement([
                'Sedentary',
                'Light',
                'Moderate',
                'Active',
                'Very Active',
            ]),
            'medical_conditions' => fake()->randomElement([
                'Diabetes Type 2',
                'High blood pressure',
                'High cholesterol',
                null,
            ]),
            'daily_calorie_target' => fake()->numberBetween(1200, 3000),
        ];
    }
}
