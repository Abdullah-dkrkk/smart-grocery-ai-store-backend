<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountUserUsage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::latest()->get();
        return $this->successResponse($discounts, 'Discounts retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:discounts,code',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'applies_to' => 'sometimes|in:all,category,product',
            'applicable_ids' => 'nullable|array',
            'applicable_ids.*' => 'integer|min:1',
            'minimum_items' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        if (in_array($validated['applies_to'] ?? 'all', ['category', 'product']) && empty($validated['applicable_ids'])) {
            return $this->errorResponse('Applicable IDs are required when applies_to is category or product.', 422);
        }

        $discount = Discount::create($validated);

        return $this->successResponse($discount, 'Discount created successfully', 201);
    }

    public function show($id)
    {
        $discount = Discount::find($id);

        if (!$discount) {
            return $this->errorResponse('Discount not found', 404);
        }

        return $this->successResponse($discount, 'Discount details retrieved');
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::find($id);

        if (!$discount) {
            return $this->errorResponse('Discount not found', 404);
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('discounts')->ignore($id)],
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'applies_to' => 'sometimes|in:all,category,product',
            'applicable_ids' => 'nullable|array',
            'applicable_ids.*' => 'integer|min:1',
            'minimum_items' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $appliesTo = $validated['applies_to'] ?? $discount->applies_to ?? 'all';
        $applicableIds = $validated['applicable_ids'] ?? $discount->applicable_ids;

        if (in_array($appliesTo, ['category', 'product']) && empty($applicableIds)) {
            return $this->errorResponse('Applicable IDs are required when applies_to is category or product.', 422);
        }

        $discount->update($validated);

        return $this->successResponse($discount, 'Discount updated successfully');
    }

    public function destroy($id)
    {
        $discount = Discount::find($id);

        if (!$discount) {
            return $this->errorResponse('Discount not found', 404);
        }

        $discount->delete();

        return $this->successResponse(null, 'Discount deleted successfully');
    }

    public function validate(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'item_count' => 'nullable|integer|min:1',
            'product_category_ids' => 'nullable|array',
            'product_category_ids.*' => 'integer|min:1',
        ]);

        $discount = Discount::where('code', strtoupper($validated['code']))->first();

        if (!$discount) {
            return $this->errorResponse('Invalid discount code.', 404);
        }

        if (!$discount->isValid(
            user: $request->user(),
            subtotal: $validated['subtotal'],
            itemCount: $validated['item_count'] ?? null,
            productCategoryIds: $validated['product_category_ids'] ?? null,
        )) {
            if ($discount->min_order_amount !== null && $validated['subtotal'] < $discount->min_order_amount) {
                return $this->errorResponse("Minimum order amount of \${$discount->min_order_amount} not met.", 400);
            }

            if ($discount->minimum_items !== null && ($validated['item_count'] ?? 0) < $discount->minimum_items) {
                return $this->errorResponse("Minimum {$discount->minimum_items} item(s) required for this discount.", 400);
            }

            if (!$discount->is_active) {
                return $this->errorResponse('This discount code is inactive.', 400);
            }

            return $this->errorResponse('This discount code is no longer valid.', 400);
        }

        $discountAmount = $discount->calculateDiscount($validated['subtotal']);

        if ($discountAmount <= 0) {
            return $this->errorResponse('This discount code does not apply to the current order.', 400);
        }

        return $this->successResponse([
            'discount' => $discount,
            'discount_amount' => $discountAmount,
            'new_subtotal' => round($validated['subtotal'] - $discountAmount, 2),
        ], 'Discount code is valid');
    }

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'item_count' => 'nullable|integer|min:1',
            'product_category_ids' => 'nullable|array',
            'product_category_ids.*' => 'integer|min:1',
        ]);

        $discount = Discount::where('code', strtoupper($validated['code']))->first();

        if (!$discount) {
            return $this->errorResponse('Invalid discount code.', 404);
        }

        if (!$discount->isValid(
            user: $request->user(),
            subtotal: $validated['subtotal'],
            itemCount: $validated['item_count'] ?? null,
            productCategoryIds: $validated['product_category_ids'] ?? null,
        )) {
            if ($discount->min_order_amount !== null && $validated['subtotal'] < $discount->min_order_amount) {
                return $this->errorResponse("Minimum order amount of \${$discount->min_order_amount} not met.", 400);
            }

            if ($discount->minimum_items !== null && ($validated['item_count'] ?? 0) < $discount->minimum_items) {
                return $this->errorResponse("Minimum {$discount->minimum_items} item(s) required for this discount.", 400);
            }

            if ($request->user() && $discount->per_user_limit !== null) {
                $userUsage = DiscountUserUsage::where('discount_id', $discount->id)
                    ->where('user_id', $request->user()->id)
                    ->count();

                if ($userUsage >= $discount->per_user_limit) {
                    return $this->errorResponse('You have reached the usage limit for this discount code.', 400);
                }
            }

            if (!$discount->is_active) {
                return $this->errorResponse('This discount code is inactive.', 400);
            }

            return $this->errorResponse('This discount code is no longer valid.', 400);
        }

        $discountAmount = $discount->calculateDiscount($validated['subtotal']);

        if ($discountAmount <= 0) {
            return $this->errorResponse('This discount code does not apply to the current order.', 400);
        }

        return $this->successResponse([
            'discount' => $discount,
            'discount_id' => $discount->id,
            'discount_code' => $discount->code,
            'discount_amount' => $discountAmount,
            'new_subtotal' => round($validated['subtotal'] - $discountAmount, 2),
        ], 'Discount applied successfully');
    }
}
