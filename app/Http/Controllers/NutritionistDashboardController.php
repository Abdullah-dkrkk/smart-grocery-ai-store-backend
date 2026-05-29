<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\MealPlan;
use App\Models\NutritionistClient;
use Illuminate\Http\Request;

class NutritionistDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $activeClients = NutritionistClient::where('nutritionist_id', $nutritionistId)
            ->where('status', 'active')->count();

        $mealPlansCreated = MealPlan::where('nutritionist_id', $nutritionistId)->count();

        $appointmentsToday = Appointment::where('nutritionist_id', $nutritionistId)
            ->whereDate('scheduled_at', today())
            ->count();

        $upcomingAppointments = Appointment::with('client')
            ->where('nutritionist_id', $nutritionistId)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest('scheduled_at')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'client_name' => $appointment->client?->name,
                    'type' => $appointment->type,
                    'scheduled_at' => $appointment->scheduled_at?->toISOString(),
                    'status' => $appointment->status,
                ];
            });

        $newClientsThisMonth = NutritionistClient::where('nutritionist_id', $nutritionistId)
            ->whereMonth('created_at', now()->month)
            ->count();

        $averageRating = 0;

        $mealPlansSummary = MealPlan::where('nutritionist_id', $nutritionistId)
            ->selectRaw('name, COUNT(*) as plan_count')
            ->groupBy('name')
            ->limit(5)
            ->get()
            ->map(function ($plan) {
                return [
                    'name' => $plan->name,
                    'client_count' => NutritionistClient::whereHas('client.mealPlans', function ($q) use ($plan) {
                        $q->where('name', $plan->name);
                    })->count(),
                    'meal_count' => 0,
                ];
            });

        return $this->successResponse([
            'active_clients' => $activeClients,
            'meal_plans_created' => $mealPlansCreated,
            'total_appointments_today' => $appointmentsToday,
            'upcoming_appointments' => $upcomingAppointments,
            'client_growth' => "{$newClientsThisMonth} new this month",
            'average_rating' => $averageRating,
            'meal_plans_summary' => $mealPlansSummary,
        ], 'Dashboard overview retrieved');
    }
}
