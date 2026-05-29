<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;

class NutritionistConsultationController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $query = Consultation::with('client')
            ->where('nutritionist_id', $nutritionistId);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $consultations = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($consultations, 'Consultations retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:users,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'notes' => 'required|string',
            'recommendations' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ]);

        $consultation = Consultation::create([
            'nutritionist_id' => $request->user()->id,
            'client_id' => $validated['client_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'notes' => $validated['notes'],
            'recommendations' => $validated['recommendations'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        return $this->successResponse($consultation->load(['client', 'appointment']), 'Consultation recorded', 201);
    }

    public function show(Request $request, $id)
    {
        $consultation = Consultation::with(['client', 'appointment'])
            ->where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse($consultation, 'Consultation retrieved');
    }

    public function update(Request $request, $id)
    {
        $consultation = Consultation::where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
            'recommendations' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ]);

        $consultation->update($validated);

        return $this->successResponse($consultation->load(['client', 'appointment']), 'Consultation updated');
    }
}
