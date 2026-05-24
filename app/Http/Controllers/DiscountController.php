<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

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
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);

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
            'code' => ['sometimes', 'string', 'max:50', \Illuminate\Validation\Rule::unique('discounts')->ignore($id)],
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
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

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $discount = Discount::where('code', strtoupper($validated['code']))->first();

        if (!$discount) {
            return $this->errorResponse('Invalid discount code.', 404);
        }

        if (!$discount->isValid()) {
            return $this->errorResponse('This discount code is no longer valid.', 400);
        }

        $discountAmount = $discount->calculateDiscount($validated['subtotal']);

        if ($discountAmount <= 0) {
            return $this->errorResponse('Minimum order amount not met.', 400);
        }

        return $this->successResponse([
            'discount' => $discount,
            'discount_amount' => $discountAmount,
            'new_subtotal' => round($validated['subtotal'] - $discountAmount, 2),
        ], 'Discount applied successfully');
    }
}
