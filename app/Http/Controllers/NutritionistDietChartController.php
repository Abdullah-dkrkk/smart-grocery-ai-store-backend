<?php

namespace App\Http\Controllers;

use App\Models\DietChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NutritionistDietChartController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $query = DietChart::with('client')
            ->where('nutritionist_id', $nutritionistId);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $charts = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($charts, 'Diet charts retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'days' => 'required|array|min:1',
            'days.*.day_number' => 'required|integer|min:1',
            'days.*.meals' => 'required|array',
            'days.*.meals.*.time' => 'required|string',
            'days.*.meals.*.meal' => 'required|string',
            'days.*.meals.*.portion' => 'nullable|string',
            'days.*.meals.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $chart = DietChart::create([
                'nutritionist_id' => $request->user()->id,
                'client_id' => $validated['client_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['days'] as $day) {
                $chart->days()->create([
                    'day_number' => $day['day_number'],
                    'meals' => $day['meals'],
                ]);
            }

            DB::commit();

            return $this->successResponse($chart->load('days'), 'Diet chart created', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create diet chart', 500);
        }
    }

    public function show(Request $request, $id)
    {
        $chart = DietChart::with(['days', 'client'])
            ->where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse($chart, 'Diet chart retrieved');
    }

    public function update(Request $request, $id)
    {
        $chart = DietChart::where('nutritionist_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'days' => 'sometimes|array',
            'days.*.day_number' => 'required_with:days|integer|min:1',
            'days.*.meals' => 'required_with:days|array',
        ]);

        DB::beginTransaction();
        try {
            $chart->update($validated);

            if (isset($validated['days'])) {
                $chart->days()->delete();
                foreach ($validated['days'] as $day) {
                    $chart->days()->create([
                        'day_number' => $day['day_number'],
                        'meals' => $day['meals'],
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse($chart->load('days'), 'Diet chart updated');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to update diet chart', 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $chart = DietChart::where('nutritionist_id', $request->user()->id)->findOrFail($id);
        $chart->days()->delete();
        $chart->delete();

        return $this->successResponse(null, 'Diet chart deleted');
    }
}
