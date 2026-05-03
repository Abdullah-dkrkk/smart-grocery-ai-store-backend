<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Items;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product catalog, categories, nutrition data, and AI-powered search"
 * )
 */
class ProductController extends Controller
{
    #[Get(
        path: "/api/products",
        tags: ["Products"],
        summary: "List all products",
        description: "Browse all active products with optional filtering.",
        parameters: [
            new Parameter(parameter: "page", name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 1)),
            new Parameter(parameter: "per_page", name: "per_page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 15)),
            new Parameter(parameter: "category_id", name: "category_id", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer")),
            new Parameter(parameter: "search", name: "search", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
            new Parameter(parameter: "sort_by", name: "sort_by", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string", enum: ["price", "name", "created_at"])),
            new Parameter(parameter: "sort_dir", name: "sort_dir", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string", enum: ["asc", "desc"])),
        ],
        responses: [
            new Response(response: 200, description: "Products retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Products retrieved successfully"),
            ])),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Product::with(['category', 'vendor'])
            ->where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        if (in_array($sortBy, ['price', 'name', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return $this->paginateResponse($products, 'Products retrieved successfully');
    }

    #[Get(
        path: "/api/products/{id}",
        tags: ["Products"],
        summary: "Get product details",
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Product details", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Product details retrieved"),
            ])),
            new Response(response: 404, description: "Product not found"),
        ]
    )]
    public function show($id)
    {
        $product = Product::with(['category', 'vendor'])->where('is_active', true)->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        return $this->successResponse($product, 'Product details retrieved');
    }

    #[Get(
        path: "/api/products/categories",
        tags: ["Products"],
        summary: "List product categories",
        responses: [
            new Response(response: 200, description: "Categories retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "array", items: new Items(type: "object")),
                new Property(property: "message", type: "string", example: "Categories retrieved successfully"),
            ])),
        ]
    )]
    public function categories()
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    #[Get(
        path: "/api/products/search",
        tags: ["Products"],
        summary: "AI-powered product search",
        parameters: [
            new Parameter(parameter: "q", name: "q", in: "query", required: true, schema: new \OpenApi\Attributes\Schema(type: "string", example: "high protein breakfast")),
            new Parameter(parameter: "dietary_type", name: "dietary_type", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
        ],
        responses: [
            new Response(response: 200, description: "Search results", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Search completed"),
            ])),
        ]
    )]
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'dietary_type' => 'nullable|string',
        ]);

        $query = Product::with(['category'])
            ->where('is_active', true);

        $searchTerms = explode(' ', $request->q);
        $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            }
        });

        if ($request->has('dietary_type')) {
            $query->whereJsonContains('nutrition_data->allergens', $request->dietary_type);
        }

        $products = $query->limit(20)->get();

        return $this->successResponse([
            'products' => $products,
            'total_results' => $products->count(),
            'query' => $request->q,
        ], 'Search completed');
    }
}
