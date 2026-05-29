<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NutritionistProfile;
use Illuminate\Http\Request;

class NutritionistController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'nutritionist');

        if ($request->has('is_approved')) {
            if ($request->boolean('is_approved')) {
                $query->whereNotNull('approved_at');
            } else {
                $query->whereNull('approved_at');
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $nutritionists = $query->latest()->paginate($request->input('per_page', 15));

        $nutritionists->getCollection()->transform(function ($n) {
            $profile = NutritionistProfile::where('nutritionist_id', $n->id)->first();

            return [
                'id' => $n->id,
                'name' => $n->name,
                'email' => $n->email,
                'phone' => $n->phone,
                'is_active' => $n->is_active,
                'is_approved' => $n->approved_at !== null,
                'approved_at' => $n->approved_at?->toISOString(),
                'specialization' => $profile?->specialization,
                'experience_years' => $profile?->experience_years,
                'consultation_fee' => $profile?->consultation_fee,
                'created_at' => $n->created_at->toISOString(),
            ];
        });

        return $this->paginateResponse($nutritionists, 'Nutritionists retrieved');
    }

    public function show($id)
    {
        $nutritionist = User::where('role', 'nutritionist')->findOrFail($id);
        $profile = NutritionistProfile::where('nutritionist_id', $nutritionist->id)->first();

        return $this->successResponse([
            'nutritionist' => $nutritionist,
            'profile' => $profile,
        ], 'Nutritionist details retrieved');
    }

    public function approve($id)
    {
        $nutritionist = User::where('role', 'nutritionist')->findOrFail($id);
        $nutritionist->update(['is_active' => true, 'approved_at' => now()]);

        return $this->successResponse($nutritionist, 'Nutritionist approved');
    }

    public function suspend($id)
    {
        $nutritionist = User::where('role', 'nutritionist')->findOrFail($id);
        $nutritionist->update(['is_active' => false]);

        return $this->successResponse($nutritionist, 'Nutritionist suspended');
    }
}
