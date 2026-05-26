<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateProductsRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
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
 *     name="Admin Products",
 *     description="Admin product management, CRUD operations, and image uploads"
 * )
 */
class AdminProductController extends Controller
{
    #[Get(
        path: "/api/admin/products",
        tags: ["Admin Products"],
        summary: "List all products (Admin)",
        description: "Get paginated list of all products including inactive ones.",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "page", name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 1)),
            new Parameter(parameter: "per_page", name: "per_page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 15)),
            new Parameter(parameter: "vendor_id", name: "vendor_id", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer")),
            new Parameter(parameter: "category_id", name: "category_id", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer")),
            new Parameter(parameter: "is_active", name: "is_active", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "boolean")),
            new Parameter(parameter: "search", name: "search", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
        ],
        responses: [
            new Response(response: 200, description: "Products retrieved successfully", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Products retrieved successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Product::with(['category', 'vendor']);

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

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
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->latest()->paginate($perPage);

        return $this->paginateResponse($products, 'Products retrieved successfully');
    }

    #[Post(
        path: "/api/admin/products",
        tags: ["Admin Products"],
        summary: "Create a new product",
        description: "Create a new product with all details including nutrition data.",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["name", "price", "category_id", "stock_quantity"], properties: [
            new Property(property: "name", type: "string", example: "Organic Almond Milk"),
            new Property(property: "description", type: "string", example: "Fresh organic almond milk, unsweetened"),
            new Property(property: "price", type: "number", format: "float", example: 4.99),
            new Property(property: "compare_at_price", type: "number", format: "float", example: 6.49),
            new Property(property: "category_id", type: "integer", example: 3),
            new Property(property: "vendor_id", type: "integer", example: 1),
            new Property(property: "stock_quantity", type: "integer", example: 100),
            new Property(property: "min_stock_threshold", type: "integer", example: 10),
            new Property(property: "is_active", type: "boolean", example: true),
            new Property(property: "is_featured", type: "boolean", example: false),
            new Property(property: "nutrition_data", type: "object"),
            new Property(property: "sku", type: "string", example: "ORG-ALM-001"),
            new Property(property: "weight_kg", type: "number", format: "float", example: 1.0),
        ])),
        responses: [
            new Response(response: 201, description: "Product created successfully", content: new JsonContent(properties: [
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
            'compare_at_price' => 'nullable|numeric|min:0|gte:price',
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'nullable|exists:users,id',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'nutrition_data' => 'nullable|array',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'weight_kg' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $product = Product::create($validated);

        return $this->successResponse($product->load(['category', 'vendor', 'images']), 'Product created successfully', 201);
    }

    #[Get(
        path: "/api/admin/products/{id}",
        tags: ["Admin Products"],
        summary: "Get product details (Admin)",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Product details retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product details retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Product not found"),
        ]
    )]
    public function show($id)
    {
        $product = Product::with(['category', 'vendor', 'images'])->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse($product, 'Product details retrieved');
    }

    #[Put(
        path: "/api/admin/products/{id}",
        tags: ["Admin Products"],
        summary: "Update a product",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        requestBody: new RequestBody(required: true, content: new JsonContent(properties: [
            new Property(property: "name", type: "string", example: "Organic Almond Milk (Updated)"),
            new Property(property: "description", type: "string", example: "Fresh organic almond milk, unsweetened, 1 liter"),
            new Property(property: "price", type: "number", format: "float", example: 5.49),
            new Property(property: "stock_quantity", type: "integer", example: 150),
            new Property(property: "is_active", type: "boolean", example: true),
            new Property(property: "nutrition_data", type: "object"),
        ])),
        responses: [
            new Response(response: 200, description: "Product updated successfully", content: new JsonContent(properties: [
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
        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'vendor_id' => 'nullable|exists:users,id',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'min_stock_threshold' => 'nullable|integer|min:0',
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

        return $this->successResponse($product->load(['category', 'vendor', 'images']), 'Product updated successfully');
    }

    #[Delete(
        path: "/api/admin/products/{id}",
        tags: ["Admin Products"],
        summary: "Delete a product",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Product deleted successfully", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "message", type: "string", example: "Product deleted successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Product not found"),
        ]
    )]
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }

    #[Post(
        path: "/api/admin/products/upload-image",
        tags: ["Admin Products"],
        summary: "Upload product image",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new MediaType(mediaType: "multipart/form-data", schema: new OASchema(required: ["image"], properties: [
            new Property(property: "image", type: "string", format: "binary"),
        ]))),
        responses: [
            new Response(response: 200, description: "Image uploaded successfully", content: new JsonContent(properties: [
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

    public function bulkUpdate(BulkUpdateProductsRequest $request)
    {
        $updated = 0;

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            if (!$product) continue;

            $product->update($item['updates']);
            $updated++;
        }

        return response()->json([
            'success' => true,
            'data' => ['updated' => $updated],
            'message' => "{$updated} product(s) updated successfully.",
        ]);
    }
}
