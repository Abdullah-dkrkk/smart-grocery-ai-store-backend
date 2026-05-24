<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\Delete;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Security;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Schema as OASchema;

/**
 * @OA\Tag(
 *     name="Vendor Products",
 *     description="Vendor product management - vendors can ONLY manage their own products"
 * )
 */
class VendorProductController extends Controller
{
    #[Get(
        path: "/api/vendor/products",
        tags: ["Vendor Products"],
        summary: "List vendor's own products",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "page", name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 1)),
            new Parameter(parameter: "per_page", name: "per_page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 15)),
            new Parameter(parameter: "is_active", name: "is_active", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "boolean")),
            new Parameter(parameter: "category_id", name: "category_id", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer")),
            new Parameter(parameter: "search", name: "search", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
        ],
        responses: [
            new Response(response: 200, description: "Products retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Your products retrieved successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $query = Product::where('vendor_id', $vendorId)
            ->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->latest()->paginate($perPage);

        return $this->paginateResponse($products, 'Your products retrieved successfully');
    }

    #[Post(
        path: "/api/vendor/products",
        tags: ["Vendor Products"],
        summary: "Create a new product (Vendor)",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["name", "price", "category_id", "stock_quantity"], properties: [
            new Property(property: "name", type: "string", example: "Organic Honey Raw"),
            new Property(property: "description", type: "string", example: "100% pure organic raw honey"),
            new Property(property: "price", type: "number", format: "float", example: 12.99),
            new Property(property: "compare_at_price", type: "number", format: "float", example: 15.99),
            new Property(property: "category_id", type: "integer", example: 5),
            new Property(property: "stock_quantity", type: "integer", example: 200),
            new Property(property: "is_active", type: "boolean", example: true),
            new Property(property: "nutrition_data", type: "object"),
            new Property(property: "sku", type: "string", example: "HON-ORG-001"),
            new Property(property: "weight_kg", type: "number", format: "float", example: 0.5),
        ])),
        responses: [
            new Response(response: 201, description: "Product created", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product created successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'nutrition_data' => 'nullable|array',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'weight_kg' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['vendor_id'] = $request->user()->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $product = Product::create($validated);

        return $this->successResponse($product->load('category'), 'Product created successfully', 201);
    }

    #[Get(
        path: "/api/vendor/products/{id}",
        tags: ["Vendor Products"],
        summary: "Get own product details",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Product details", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product details retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Product not found"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $product = Product::where('vendor_id', $request->user()->id)
            ->with('category')
            ->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse($product, 'Product details retrieved');
    }

    #[Put(
        path: "/api/vendor/products/{id}",
        tags: ["Vendor Products"],
        summary: "Update own product",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        requestBody: new RequestBody(required: true, content: new JsonContent(properties: [
            new Property(property: "name", type: "string"),
            new Property(property: "description", type: "string"),
            new Property(property: "price", type: "number", format: "float"),
            new Property(property: "stock_quantity", type: "integer"),
            new Property(property: "is_active", type: "boolean"),
            new Property(property: "nutrition_data", type: "object"),
        ])),
        responses: [
            new Response(response: 200, description: "Product updated", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product updated successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Product not found"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(Request $request, $id)
    {
        $product = Product::where('vendor_id', $request->user()->id)->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'nutrition_data' => 'nullable|array',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products')->ignore($id)],
            'weight_kg' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string',
        ]);

        if (isset($validated['name']) && $product->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return $this->successResponse($product->load('category'), 'Product updated successfully');
    }

    #[Delete(
        path: "/api/vendor/products/{id}",
        tags: ["Vendor Products"],
        summary: "Delete own product",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Product deleted", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "message", type: "string", example: "Product deleted successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Product not found"),
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $product = Product::where('vendor_id', $request->user()->id)->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }

    #[Post(
        path: "/api/vendor/products/upload-image",
        tags: ["Vendor Products"],
        summary: "Upload product image",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new MediaType(mediaType: "multipart/form-data", schema: new OASchema(required: ["image"], properties: [
            new Property(property: "image", type: "string", format: "binary"),
        ]))),
        responses: [
            new Response(response: 200, description: "Image uploaded", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", properties: [
                    new Property(property: "image_url", type: "string", example: "/storage/products/image123.jpg"),
                ], type: "object"),
                new Property(property: "message", type: "string", example: "Image uploaded successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = $request->file('image')->store('products');

        return $this->successResponse(['image_url' => Storage::url($path)], 'Image uploaded successfully');
    }

    #[Get(
        path: "/api/vendor/products/stats",
        tags: ["Vendor Products"],
        summary: "Get vendor product stats",
        security: [["sanctum" => []]],
        responses: [
            new Response(response: 200, description: "Stats retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Vendor stats retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function stats(Request $request)
    {
        $vendorId = $request->user()->id;

        $totalProducts = Product::where('vendor_id', $vendorId)->count();
        $activeProducts = Product::where('vendor_id', $vendorId)->where('is_active', true)->count();
        $inactiveProducts = Product::where('vendor_id', $vendorId)->where('is_active', false)->count();
        $lowStockProducts = Product::where('vendor_id', $vendorId)
            ->whereColumn('stock_quantity', '<=', 'min_stock_threshold')
            ->where('is_active', true)
            ->count();
        $outOfStockProducts = Product::where('vendor_id', $vendorId)
            ->where('stock_quantity', 0)
            ->where('is_active', true)
            ->count();

        $totalRevenue = \App\Models\OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.vendor_id', $vendorId)
            ->sum('order_items.subtotal');

        return $this->successResponse([
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'inactive_products' => $inactiveProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'total_revenue' => (float) $totalRevenue,
        ], 'Vendor stats retrieved');
    }

    public function analytics(Request $request, $id)
    {
        $product = Product::where('vendor_id', $request->user()->id)->findOrFail($id);

        $totalSold = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn($q) => $q->whereIn('status', ['delivered', 'shipped']))
            ->sum('quantity');

        $revenue = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn($q) => $q->whereIn('status', ['delivered']))
            ->sum(DB::raw('quantity * unit_price'));

        return response()->json([
            'success' => true,
            'data' => [
                'total_sold' => (int) $totalSold,
                'total_revenue' => (float) $revenue,
                'current_stock' => $product->stock_quantity,
                'views' => 0,
                'orders_last_30_days' => OrderItem::where('product_id', $product->id)
                    ->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
                    ->sum('quantity'),
            ],
        ]);
    }
}
