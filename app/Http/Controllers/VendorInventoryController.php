<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class VendorInventoryController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $products = Product::where('vendor_id', $vendorId)
            ->orderBy('stock_quantity', 'asc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock_quantity' => $product->stock_quantity,
                    'min_stock_threshold' => $product->min_stock_threshold,
                    'is_low_stock' => $product->isLowStock(),
                    'is_out_of_stock' => !$product->isInStock(),
                    'is_active' => $product->is_active,
                ];
            });

        return $this->successResponse($products, 'Inventory retrieved');
    }

    public function update(Request $request, $productId)
    {
        $vendorId = $request->user()->id;

        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_threshold' => 'required|integer|min:0',
        ]);

        $product = Product::where('id', $productId)->where('vendor_id', $vendorId)->firstOrFail();
        $product->update($validated);

        return $this->successResponse([
            'id' => $product->id,
            'name' => $product->name,
            'stock_quantity' => $product->stock_quantity,
            'min_stock_threshold' => $product->min_stock_threshold,
            'is_low_stock' => $product->isLowStock(),
        ], 'Inventory updated');
    }
}
