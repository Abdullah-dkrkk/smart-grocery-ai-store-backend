<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class CustomerPaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)->latest()->get();
        return $this->successResponse($methods, 'Payment methods retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string|max:19',
            'card_holder' => 'required|string|max:255',
            'expiry_month' => 'required|integer|min:1|max:12',
            'expiry_year' => 'required|integer|min:' . date('Y'),
            'is_default' => 'boolean',
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'card_last_four' => substr($validated['card_number'], -4),
            'card_holder' => $validated['card_holder'],
            'expiry_month' => $validated['expiry_month'],
            'expiry_year' => $validated['expiry_year'],
            'is_default' => $validated['is_default'] ?? false,
        ];

        if (!empty($data['is_default'])) {
            PaymentMethod::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $method = PaymentMethod::create($data);

        return $this->successResponse($method, 'Payment method added', 201);
    }

    public function destroy(Request $request, $id)
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);
        $method->delete();

        return $this->successResponse(null, 'Payment method deleted');
    }
}
