<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)->latest()->get();
        return $this->successResponse($addresses, 'Addresses retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'street' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        if (!empty($validated['is_default'])) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return $this->successResponse($address, 'Address created', 201);
    }

    public function show(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        return $this->successResponse($address, 'Address retrieved');
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'street' => 'sometimes|string',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'zip' => 'sometimes|string|max:20',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Address::where('user_id', $request->user()->id)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->successResponse($address, 'Address updated');
    }

    public function destroy(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return $this->successResponse(null, 'Address deleted');
    }
}
