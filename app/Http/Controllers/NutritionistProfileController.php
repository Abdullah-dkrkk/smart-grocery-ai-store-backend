<?php

namespace App\Http\Controllers;

use App\Models\NutritionistProfile;
use Illuminate\Http\Request;

class NutritionistProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = NutritionistProfile::where('nutritionist_id', $request->user()->id)->first();

        if (!$profile) {
            return $this->successResponse(null, 'Profile not set up yet');
        }

        return $this->successResponse($profile, 'Profile retrieved');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bio' => 'nullable|string',
            'specialization' => 'nullable|string|max:255',
            'qualifications' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'profile_image' => 'nullable|string|url',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        $profile = NutritionistProfile::updateOrCreate(
            ['nutritionist_id' => $request->user()->id],
            $validated
        );

        return $this->successResponse($profile, 'Profile updated');
    }
}
