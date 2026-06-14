<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Http\Request;

class VendorStoreController extends Controller
{
    public function show(Request $request)
    {
        $store = VendorStore::where('vendor_id', $request->user()->id)->first();

        if (!$store) {
            return $this->successResponse(null, 'Store not set up yet');
        }

        return $this->successResponse($store, 'Store settings retrieved');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'sometimes|string|max:255',
            'store_description' => 'nullable|string',
            'store_logo_url' => 'nullable|string|url',
            'store_banner_url' => 'nullable|string|url',
            'return_policy' => 'nullable|string',
            'shipping_policy' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        $store = VendorStore::updateOrCreate(
            ['vendor_id' => $request->user()->id],
            $validated
        );

        return $this->successResponse($store, 'Store settings updated');
    }

    public function publicShow($slug)
    {
        $vendor = User::where('role', 'vendor')
            ->where('is_active', true)
            ->whereHas('store', function ($q) use ($slug) {
                $q->where('store_name', 'like', "%{$slug}%")
                  ->orWhereHas('vendor', function ($q2) use ($slug) {
                      $q2->where('name', 'like', "%{$slug}%");
                  });
            })
            ->with('store')
            ->first();

        if (!$vendor) {
            $vendor = User::where('role', 'vendor')
                ->where('is_active', true)
                ->where('name', 'like', "%{$slug}%")
                ->with('store')
                ->first();
        }

        if (!$vendor) {
            return $this->errorResponse('Vendor not found', 404);
        }

        $products = Product::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->with('category')
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'vendor' => $vendor,
                'store' => $vendor->store,
                'products' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ],
            'message' => 'Vendor store retrieved successfully',
        ]);
    }
}
