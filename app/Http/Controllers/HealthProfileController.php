<?php

namespace App\Http\Controllers;

use App\Models\HealthProfile;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Health Profile",
 *     description="User health profile management"
 * )
 */
class HealthProfileController extends Controller
{
    #[Get(
        path: "/api/user/health-profile",
        tags: ["Health Profile"],
        summary: "Get health profile",
        security: [["sanctum" => []]],
        responses: [
            new Response(response: 200, description: "Health profile retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Health profile retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 404, description: "Health profile not found"),
        ]
    )]
    public function show(Request $request)
    {
        $profile = HealthProfile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return $this->errorResponse('Health profile not found', 404);
        }

        return $this->successResponse($profile, 'Health profile retrieved');
    }

    #[Put(
        path: "/api/user/health-profile",
        tags: ["Health Profile"],
        summary: "Update health profile",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(properties: [
            new Property(property: "age", type: "integer", example: 30),
            new Property(property: "weight", type: "number", format: "float", example: 70.5),
            new Property(property: "height", type: "number", format: "float", example: 175.0),
            new Property(property: "goals", type: "string", example: "weight_loss"),
            new Property(property: "allergies", type: "array", items: new \OpenApi\Attributes\Items(type: "string"), example: ["peanuts", "shellfish"]),
            new Property(property: "dietary_type", type: "string", example: "vegetarian"),
            new Property(property: "activity_level", type: "string", example: "moderate"),
            new Property(property: "medical_conditions", type: "string", example: "none"),
            new Property(property: "daily_calorie_target", type: "integer", example: 2000),
        ])),
        responses: [
            new Response(response: 200, description: "Health profile updated", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Health profile updated"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(Request $request)
    {
        $validated = $request->validate([
            'age' => 'nullable|integer|min:1|max:150',
            'weight' => 'nullable|numeric|min:1|max:500',
            'height' => 'nullable|numeric|min:1|max:300',
            'goals' => 'nullable|string|max:255',
            'allergies' => 'nullable|array',
            'dietary_type' => 'nullable|string|max:50',
            'activity_level' => 'nullable|string|max:50',
            'medical_conditions' => 'nullable|string',
            'daily_calorie_target' => 'nullable|integer|min:500|max:10000',
        ]);

        if (isset($validated['weight']) && isset($validated['height'])) {
            $heightM = $validated['height'] / 100;
            $validated['bmi'] = round($validated['weight'] / ($heightM * $heightM), 1);
        }

        $profile = HealthProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return $this->successResponse($profile, 'Health profile updated');
    }
}
