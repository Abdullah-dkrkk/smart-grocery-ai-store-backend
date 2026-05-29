<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\Product;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'vendor');

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

        $vendors = $query->latest()->paginate($request->input('per_page', 15));

        $vendors->getCollection()->transform(function ($vendor) {
            $store = VendorStore::where('vendor_id', $vendor->id)->first();

            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'is_active' => $vendor->is_active,
                'is_approved' => $vendor->approved_at !== null,
                'approved_at' => $vendor->approved_at?->toISOString(),
                'store_name' => $store?->store_name,
                'store_logo_url' => $store?->store_logo_url,
                'product_count' => Product::where('vendor_id', $vendor->id)->count(),
                'created_at' => $vendor->created_at->toISOString(),
            ];
        });

        return $this->paginateResponse($vendors, 'Vendors retrieved');
    }

    public function show($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $store = VendorStore::where('vendor_id', $vendor->id)->first();
        $productCount = Product::where('vendor_id', $vendor->id)->count();

        return $this->successResponse([
            'vendor' => $vendor,
            'store' => $store,
            'product_count' => $productCount,
        ], 'Vendor details retrieved');
    }

    public function approve($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $vendor->update(['is_active' => true, 'approved_at' => now()]);

        VendorStore::where('vendor_id', $vendor->id)->update(['is_approved' => true, 'approved_at' => now()]);

        return $this->successResponse($vendor, 'Vendor approved');
    }

    public function suspend($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $vendor->update(['is_active' => false]);

        return $this->successResponse($vendor, 'Vendor suspended');
    }

    public function products($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);

        $products = Product::where('vendor_id', $vendor->id)
            ->latest()
            ->paginate(request('per_page', 15));

        return $this->paginateResponse($products, 'Vendor products retrieved');
    }
}
