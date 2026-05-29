<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use Illuminate\Http\Request;

class CustomerNutritionPlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = MealPlan::with('nutritionist')
            ->where('client_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'duration_days' => $plan->duration_days,
                    'daily_calories' => $plan->daily_calories,
                    'nutritionist_name' => $plan->nutritionist?->name,
                    'created_at' => $plan->created_at->toISOString(),
                ];
            });

        return $this->successResponse($plans, 'Nutrition plans retrieved');
    }

    public function show(Request $request, $id)
    {
        $plan = MealPlan::with(['meals', 'nutritionist'])
            ->where('client_id', $request->user()->id)
            ->findOrFail($id);

        $planData = [
            'id' => $plan->id,
            'name' => $plan->name,
            'description' => $plan->description,
            'duration_days' => $plan->duration_days,
            'daily_calories' => $plan->daily_calories,
            'nutritionist_name' => $plan->nutritionist?->name,
            'meals' => $plan->meals->groupBy('day')->map(function ($meals, $day) {
                return [
                    'day' => (int) $day,
                    'meals' => $meals->map(function ($meal) {
                        return [
                            'id' => $meal->id,
                            'meal_type' => $meal->meal_type,
                            'name' => $meal->name,
                            'calories' => $meal->calories,
                            'protein_g' => $meal->protein_g,
                            'carbs_g' => $meal->carbs_g,
                            'fat_g' => $meal->fat_g,
                            'notes' => $meal->notes,
                        ];
                    }),
                ];
            })->values(),
            'progress' => [
                'days_completed' => min(now()->diffInDays($plan->created_at), $plan->duration_days),
                'total_days' => $plan->duration_days,
            ],
        ];

        return $this->successResponse($planData, 'Nutrition plan retrieved');
    }
}
