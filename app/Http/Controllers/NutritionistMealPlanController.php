<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NutritionistMealPlanController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $query = MealPlan::with('client')
            ->where('nutritionist_id', $nutritionistId);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $plans = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($plans, 'Meal plans retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_days' => 'required|integer|min:1',
            'daily_calories' => 'nullable|integer|min:0',
            'meals' => 'required|array|min:1',
            'meals.*.day' => 'required|integer|min:1',
            'meals.*.meal_type' => 'required|in:breakfast,lunch,dinner,snack',
            'meals.*.name' => 'required|string|max:255',
            'meals.*.calories' => 'nullable|integer|min:0',
            'meals.*.protein_g' => 'nullable|numeric|min:0',
            'meals.*.carbs_g' => 'nullable|numeric|min:0',
            'meals.*.fat_g' => 'nullable|numeric|min:0',
            'meals.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $mealPlan = MealPlan::create([
                'nutritionist_id' => $request->user()->id,
                'client_id' => $validated['client_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'duration_days' => $validated['duration_days'],
                'daily_calories' => $validated['daily_calories'] ?? null,
            ]);

            foreach ($validated['meals'] as $meal) {
                $mealPlan->meals()->create($meal);
            }

            DB::commit();

            return $this->successResponse($mealPlan->load('meals'), 'Meal plan created', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create meal plan: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        $plan = MealPlan::with(['meals', 'client'])
            ->where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse($plan, 'Meal plan retrieved');
    }

    public function update(Request $request, $id)
    {
        $plan = MealPlan::where('nutritionist_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'nullable|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'duration_days' => 'sometimes|integer|min:1',
            'daily_calories' => 'nullable|integer|min:0',
            'meals' => 'sometimes|array',
            'meals.*.day' => 'required_with:meals|integer|min:1',
            'meals.*.meal_type' => 'required_with:meals|in:breakfast,lunch,dinner,snack',
            'meals.*.name' => 'required_with:meals|string|max:255',
            'meals.*.calories' => 'nullable|integer|min:0',
            'meals.*.protein_g' => 'nullable|numeric|min:0',
            'meals.*.carbs_g' => 'nullable|numeric|min:0',
            'meals.*.fat_g' => 'nullable|numeric|min:0',
            'meals.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $plan->update($validated);

            if (isset($validated['meals'])) {
                $plan->meals()->delete();
                foreach ($validated['meals'] as $meal) {
                    $plan->meals()->create($meal);
                }
            }

            DB::commit();

            return $this->successResponse($plan->load('meals'), 'Meal plan updated');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to update meal plan', 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $plan = MealPlan::where('nutritionist_id', $request->user()->id)->findOrFail($id);
        $plan->meals()->delete();
        $plan->delete();

        return $this->successResponse(null, 'Meal plan deleted');
    }
}
