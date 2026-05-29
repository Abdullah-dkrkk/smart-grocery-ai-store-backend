<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class NutritionistAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $query = Appointment::with('client')
            ->where('nutritionist_id', $nutritionistId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('from')) {
            $query->whereDate('scheduled_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('scheduled_at', '<=', $request->to);
        }

        $appointments = $query->latest('scheduled_at')->paginate($request->input('per_page', 15));

        return $this->paginateResponse($appointments, 'Appointments retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:users,id',
            'scheduled_at' => 'required|date',
            'type' => 'required|in:consultation,follow-up',
            'notes' => 'nullable|string',
        ]);

        $appointment = Appointment::create([
            'nutritionist_id' => $request->user()->id,
            'client_id' => $validated['client_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'type' => $validated['type'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse($appointment->load('client'), 'Appointment created', 201);
    }

    public function show(Request $request, $id)
    {
        $appointment = Appointment::with('client')
            ->where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse($appointment, 'Appointment retrieved');
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled',
        ]);

        $appointment = Appointment::where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        $appointment->update(['status' => $validated['status']]);

        return $this->successResponse($appointment->load('client'), 'Appointment status updated');
    }
}
