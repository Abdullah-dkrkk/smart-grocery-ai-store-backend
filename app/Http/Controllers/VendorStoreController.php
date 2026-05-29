<?php

namespace App\Http\Controllers;

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
}
