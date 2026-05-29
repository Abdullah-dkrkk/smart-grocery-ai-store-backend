<?php

namespace App\Http\Controllers;

use App\Models\NutritionistClient;
use App\Models\MealPlan;
use App\Models\Consultation;
use Illuminate\Http\Request;

class NutritionistClientController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $query = NutritionistClient::with('client')
            ->where('nutritionist_id', $nutritionistId);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($clients, 'Clients retrieved');
    }

    public function show(Request $request, $id)
    {
        $nutritionistId = $request->user()->id;

        $assignment = NutritionistClient::with('client')
            ->where('nutritionist_id', $nutritionistId)
            ->where('id', $id)
            ->firstOrFail();

        $client = $assignment->client;

        $mealPlans = MealPlan::where('nutritionist_id', $nutritionistId)
            ->where('client_id', $client->id)
            ->get();

        $consultations = Consultation::with('appointment')
            ->where('nutritionist_id', $nutritionistId)
            ->where('client_id', $client->id)
            ->latest()
            ->get();

        $healthProfile = $client->healthProfile;

        return $this->successResponse([
            'assignment' => $assignment,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'avatar_url' => $client->avatar_url,
            ],
            'health_profile' => $healthProfile,
            'meal_plans' => $mealPlans,
            'consultations' => $consultations,
        ], 'Client details retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_user_id' => 'required|integer|exists:users,id',
        ]);

        $nutritionistId = $request->user()->id;

        $exists = NutritionistClient::where('nutritionist_id', $nutritionistId)
            ->where('client_id', $validated['client_user_id'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Client already assigned', 409);
        }

        $assignment = NutritionistClient::create([
            'nutritionist_id' => $nutritionistId,
            'client_id' => $validated['client_user_id'],
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        return $this->successResponse($assignment->load('client'), 'Client assigned', 201);
    }
}
