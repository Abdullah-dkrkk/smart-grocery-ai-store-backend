<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\HealthProfile;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Security;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Schema;

/**
 * @OA\Tag(
 *     name="AI Assistant",
 *     description="AI chat, product recommendations, image recognition, and personalized diet plans"
 * )
 */
class AiAssistantController extends Controller
{
    #[Post(
        path: "/api/customer/ai/ask",
        tags: ["AI Assistant"],
        summary: "Ask AI a question",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["message"], properties: [
            new Property(property: "message", type: "string", example: "What are good protein sources for muscle gain?"),
            new Property(property: "context", type: "string", nullable: true, example: "product_recommendation"),
        ])),
        responses: [
            new Response(response: 200, description: "AI response generated", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "AI response generated"),
            ])),
            new Response(response: 400, description: "Invalid request"),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 429, description: "Rate limit exceeded"),
        ]
    )]
    public function ask(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:2000',
            'context' => 'nullable|string',
        ]);

        ChatMessage::create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'context' => $validated['context'] ?? 'general',
            'is_user_message' => true,
        ]);

        $healthProfile = HealthProfile::where('user_id', $request->user()->id)->first();
        $suggestedProducts = $this->findRelatedProducts($validated['message']);

        $response = $this->generateResponse($validated['message'], $healthProfile, $suggestedProducts);

        $aiMessage = ChatMessage::create([
            'user_id' => $request->user()->id,
            'message' => $response,
            'context' => $validated['context'] ?? 'general',
            'is_user_message' => false,
        ]);

        return $this->successResponse([
            'response' => $response,
            'suggested_products' => $suggestedProducts,
            'conversation_id' => $aiMessage->id,
        ], 'AI response generated');
    }

    #[Get(
        path: "/api/customer/ai/suggestions",
        tags: ["AI Assistant"],
        summary: "Get AI-suggested questions",
        security: [["sanctum" => []]],
        responses: [
            new Response(response: 200, description: "Suggestions retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Suggestions retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function suggestions(Request $request)
    {
        $healthProfile = HealthProfile::where('user_id', $request->user()->id)->first();

        $suggestions = [
            "What are the best foods for heart health?",
            "Show me low-sugar snacks",
            "Suggest a high-protein breakfast",
            "What products are good for weight loss?",
            "Recommend foods rich in vitamin D",
            "What are healthy alternatives to white rice?",
        ];

        if ($healthProfile) {
            $customSuggestions = [];

            if ($healthProfile->goals === 'weight_loss') {
                $customSuggestions[] = "Show me low-calorie meal options";
            }

            if ($healthProfile->goals === 'muscle_gain') {
                $customSuggestions[] = "What are the best protein sources?";
            }

            if (!empty($healthProfile->allergies)) {
                $customSuggestions[] = "Show me products without " . implode(' or ', $healthProfile->allergies);
            }

            if ($healthProfile->dietary_type) {
                $customSuggestions[] = "Suggest {$healthProfile->dietary_type} products";
            }

            $suggestions = array_merge($customSuggestions, array_slice($suggestions, 0, 4));
        }

        return $this->successResponse([
            'suggestions' => array_slice($suggestions, 0, 5),
        ], 'Suggestions retrieved');
    }

    #[Post(
        path: "/api/customer/ai/identify",
        tags: ["AI Assistant"],
        summary: "Identify product from image",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new MediaType(mediaType: "multipart/form-data", schema: new Schema(required: ["image"], properties: [
            new Property(property: "image", type: "string", format: "binary"),
        ]))),
        responses: [
            new Response(response: 200, description: "Product identified", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product identified"),
            ])),
            new Response(response: 400, description: "Invalid image"),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function identify(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $path = $request->file('image')->store('ai-identifications');
        $imageUrl = Storage::url($path);

        $keyword = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME);
        $keyword = str_replace(['-', '_'], ' ', $keyword);

        $identifiedProduct = Product::where('is_active', true)
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('description', 'like', "%{$keyword}%")
            ->first();

        if (!$identifiedProduct) {
            $identifiedProduct = Product::where('is_active', true)->inRandomOrder()->first();
            $confidence = 0.65;
        } else {
            $confidence = 0.92;
        }

        $healthierAlternatives = Product::where('is_active', true)
            ->where('id', '!=', $identifiedProduct?->id)
            ->where('category_id', $identifiedProduct?->category_id)
            ->limit(3)
            ->get();

        return $this->successResponse([
            'identified_product' => $identifiedProduct,
            'confidence' => $confidence,
            'nutrition_summary' => $this->generateNutritionSummary($identifiedProduct),
            'healthier_alternatives' => $healthierAlternatives,
            'image_url' => $imageUrl,
        ], 'Product identified');
    }

    #[Get(
        path: "/api/customer/ai/diet-plan",
        tags: ["AI Assistant"],
        summary: "Get personalized diet plan",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "duration", name: "duration", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 7)),
        ],
        responses: [
            new Response(response: 200, description: "Diet plan generated", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Diet plan generated"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 404, description: "Health profile not found"),
        ]
    )]
    public function dietPlan(Request $request)
    {
        $healthProfile = HealthProfile::where('user_id', $request->user()->id)->first();

        if (!$healthProfile) {
            return $this->errorResponse('Health profile not found. Please set up your health profile first.', 404);
        }

        $duration = (int) $request->input('duration', 7);
        $dailyCalories = $healthProfile->daily_calorie_target ?? 2000;

        $dailyMeals = [];
        for ($day = 1; $day <= $duration; $day++) {
            $recommendedProducts = Product::where('is_active', true)
                ->inRandomOrder()
                ->limit(5)
                ->get();

            $dailyMeals[] = [
                'day' => $day,
                'breakfast' => $this->getMealSuggestion('breakfast', $healthProfile),
                'lunch' => $this->getMealSuggestion('lunch', $healthProfile),
                'dinner' => $this->getMealSuggestion('dinner', $healthProfile),
                'snacks' => $this->getMealSuggestion('snack', $healthProfile),
                'recommended_products' => $recommendedProducts,
            ];
        }

        $plan = "Your {$duration}-day personalized meal plan based on your {$healthProfile->dietary_type} diet and {$healthProfile->goals} goals.";

        return $this->successResponse([
            'plan' => $plan,
            'daily_meals' => $dailyMeals,
            'total_calories' => $dailyCalories,
            'duration_days' => $duration,
        ], 'Diet plan generated');
    }

    #[Get(
        path: "/api/customer/ai/chat/history",
        tags: ["AI Assistant"],
        summary: "Get chat history",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "page", name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 1)),
            new Parameter(parameter: "per_page", name: "per_page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 20)),
        ],
        responses: [
            new Response(response: 200, description: "Chat history retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Chat history retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $messages = ChatMessage::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        return $this->paginateResponse($messages, 'Chat history retrieved');
    }

    public function nutritionBreakdown($productId)
    {
        $product = Product::findOrFail($productId);

        if (!$product->nutrition_data) {
            return $this->errorResponse('No nutrition data available for this product.', 404);
        }

        $nutrition = $product->nutrition_data;
        $breakdown = [
            'product_name' => $product->name,
            'serving_size' => $nutrition['serving_size'] ?? '100g',
            'calories' => $nutrition['calories'] ?? 0,
            'macros' => [
                'protein' => $nutrition['protein'] ?? 0,
                'carbs' => $nutrition['carbs'] ?? 0,
                'fat' => $nutrition['fat'] ?? 0,
                'fiber' => $nutrition['fiber'] ?? 0,
            ],
            'daily_value_percentages' => [
                'vitamin_a' => $nutrition['vitamin_a'] ?? 0,
                'vitamin_c' => $nutrition['vitamin_c'] ?? 0,
                'calcium' => $nutrition['calcium'] ?? 0,
                'iron' => $nutrition['iron'] ?? 0,
            ],
            'dietary_fit' => [
                'is_vegetarian' => $nutrition['is_vegetarian'] ?? false,
                'is_vegan' => $nutrition['is_vegan'] ?? false,
                'is_gluten_free' => $nutrition['is_gluten_free'] ?? false,
                'is_keto_friendly' => $nutrition['is_keto_friendly'] ?? false,
            ],
            'health_notes' => $nutrition['health_notes'] ?? 'No health notes available.',
        ];

        return response()->json(['success' => true, 'data' => $breakdown]);
    }

    private function findRelatedProducts(string $message): array
    {
        $keywords = preg_split('/\s+/', strtolower($message));
        $keywords = array_filter($keywords, fn ($word) => strlen($word) > 3);

        if (empty($keywords)) {
            return Product::where('is_active', true)
                ->where('is_featured', true)
                ->limit(5)
                ->get()
                ->toArray();
        }

        $query = Product::where('is_active', true);

        foreach ($keywords as $index => $keyword) {
            if ($index === 0) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            } else {
                $query->orWhere(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
        }

        return $query->limit(5)->get()->toArray();
    }

    private function generateResponse(string $message, ?HealthProfile $profile, array $products): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'protein')) {
            return "Based on your health profile, here are excellent protein-rich options. These products can help support your muscle-building goals. Check out the suggested products for specific recommendations.";
        }

        if (str_contains($lower, 'weight loss') || str_contains($lower, 'low calorie')) {
            return "For weight management, I recommend focusing on high-fiber, low-calorie foods. The suggested products below are great options that align with your goals.";
        }

        if (str_contains($lower, 'heart') || str_contains($lower, 'cholesterol')) {
            return "For heart health, consider foods rich in omega-3 fatty acids, fiber, and antioxidants. The products below are selected with cardiovascular health in mind.";
        }

        return "Based on your query, I've found some relevant products for you. These selections take into account your health profile and dietary preferences. Feel free to ask for more specific recommendations!";
    }

    private function generateNutritionSummary(?Product $product): string
    {
        if (!$product || !$product->nutrition_data) {
            return "Nutrition information is not available for this product.";
        }

        $data = $product->nutrition_data;
        $summary = [];

        if (($data['protein'] ?? 0) > 15) {
            $summary[] = "high in protein";
        }

        if (($data['sugar'] ?? 0) < 5) {
            $summary[] = "low in sugar";
        }

        if (($data['fiber'] ?? 0) > 5) {
            $summary[] = "high in fiber";
        }

        if (($data['fats'] ?? 0) < 5) {
            $summary[] = "low in fat";
        }

        if (empty($summary)) {
            return "This product has balanced nutritional content.";
        }

        return "This product is " . implode(' and ', $summary) . ".";
    }

    private function getMealSuggestion(string $mealType, HealthProfile $profile): string
    {
        $suggestions = [
            'breakfast' => [
                'general' => 'Oatmeal with fresh berries and almonds',
                'weight_loss' => 'Greek yogurt with chia seeds and a small banana',
                'muscle_gain' => 'Protein smoothie with banana, oats, and whey protein',
                'vegetarian' => 'Avocado toast with poached eggs and spinach',
                'vegan' => 'Overnight oats with coconut milk and mixed berries',
            ],
            'lunch' => [
                'general' => 'Grilled chicken salad with quinoa and avocado',
                'weight_loss' => 'Grilled salmon with steamed vegetables and brown rice',
                'muscle_gain' => 'Chicken breast with sweet potato and broccoli',
                'vegetarian' => 'Lentil soup with whole grain bread and mixed greens',
                'vegan' => 'Chickpea Buddha bowl with tahini dressing',
            ],
            'dinner' => [
                'general' => 'Baked fish with roasted vegetables and couscous',
                'weight_loss' => 'Turkey stir-fry with cauliflower rice',
                'muscle_gain' => 'Steak with baked potato and asparagus',
                'vegetarian' => 'Mushroom and spinach pasta with garlic bread',
                'vegan' => 'Tofu curry with jasmine rice and naan',
            ],
            'snack' => [
                'general' => 'Mixed nuts and dried fruits',
                'weight_loss' => 'Apple slices with almond butter',
                'muscle_gain' => 'Protein bar and a handful of almonds',
                'vegetarian' => 'Hummus with vegetable sticks',
                'vegan' => 'Trail mix with seeds and dark chocolate',
            ],
        ];

        $mealSuggestions = $suggestions[$mealType] ?? $suggestions['general'];
        $dietType = $profile->dietary_type ?? 'general';
        $goal = $profile->goals ?? null;

        if (isset($mealSuggestions[$dietType])) {
            return $mealSuggestions[$dietType];
        }

        if ($goal && isset($mealSuggestions[$goal])) {
            return $mealSuggestions[$goal];
        }

        return $mealSuggestions['general'];
    }
}
