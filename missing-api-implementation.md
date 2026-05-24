# Missing APIs Implementation Guide

> Based on Section 12 of `api-documentation.md` — detailed guide for implementing missing backend APIs in the Laravel project.

---

## Priority Legend

| Icon | Priority | Description |
|------|----------|-------------|
| 🔴 | **High** | Blocking frontend features |
| 🟡 | **Medium** | Important for completeness |
| 🟢 | **Low** | Nice-to-have enhancements |

---

## 🔴 HIGH PRIORITY

### 1. Refactor Auth Register/Login to use FormRequest

**Location:** `app/Http/Controllers/AuthController.php`

**Current:** Validation inline in controller.  
**Required:** Dedicated `FormRequest` classes as per `.cursorrules`.

#### Steps:

**a) Create `RegisterRequest`**

```bash
php artisan make:request RegisterRequest
```

**`app/Http/Requests/RegisterRequest.php`:**
```php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
```

**b) Create `LoginRequest`**

```bash
php artisan make:request LoginRequest
```

**`app/Http/Requests/LoginRequest.php`:**
```php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

**c) Update Controller**

```php
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

public function register(RegisterRequest $request)
{
    $validated = $request->validated();
    // ... rest of logic
}

public function login(LoginRequest $request)
{
    $validated = $request->validated();
    // ... rest of logic
}
```

---

### 2. Customer Order Cancel Endpoint

**Endpoint:** `PUT /api/customer/orders/{id}/cancel`  
**Auth:** Sanctum — `customer` role  
**Rate Limit:** 10 requests per minute

#### Add Route

**`routes/api.php`:**
```php
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::put('/customer/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
```

#### Add Controller Method

**`app/Http/Controllers/OrderController.php`:**
```php
public function cancel(Order $order)
{
    // Verify ownership
    if ($order->user_id !== auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found.',
        ], 404);
    }

    // Verify status allows cancellation
    if (!in_array($order->status, ['pending', 'processing'])) {
        return response()->json([
            'success' => false,
            'message' => 'Order cannot be cancelled in its current status.',
        ], 400);
    }

    $order->status = 'cancelled';
    $order->cancelled_at = now();
    $order->save();

    // Restore stock quantities
    foreach ($order->items as $item) {
        $item->product->increment('stock_quantity', $item->quantity);
    }

    return response()->json([
        'success' => true,
        'data' => $order->load('items.product'),
        'message' => 'Order cancelled successfully.',
    ]);
}
```

#### Validation Rules
- Order must belong to authenticated user
- Order status must be `pending` or `processing`
- Cancelled orders cannot be re-cancelled

#### Response (200)
```json
{
  "success": true,
  "data": { "...order with items..." },
  "message": "Order cancelled successfully."
}
```

#### Response (400)
```json
{
  "success": false,
  "message": "Order cannot be cancelled in its current status."
}
```

---

### 3. Customer Order Tracking Endpoint

**Endpoint:** `GET /api/customer/orders/{id}/track`  
**Auth:** Sanctum — `customer` role  
**Rate Limit:** 30 requests per minute

#### Add Route

**`routes/api.php`:**
```php
Route::get('/customer/orders/{order}/track', [OrderController::class, 'track']);
```

#### Add Controller Method

**`app/Http/Controllers/OrderController.php`:**
```php
public function track(Order $order)
{
    if ($order->user_id !== auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found.',
        ], 404);
    }

    $timeline = [];

    $timeline[] = [
        'status' => 'pending',
        'timestamp' => $order->created_at->toISOString(),
        'note' => 'Order placed successfully.',
    ];

    if ($order->paid_at) {
        $timeline[] = [
            'status' => 'paid',
            'timestamp' => $order->paid_at->toISOString(),
            'note' => 'Payment confirmed.',
        ];
    }

    if ($order->status === 'processing' || in_array($order->status, ['shipped', 'delivered'])) {
        $timeline[] = [
            'status' => 'processing',
            'timestamp' => $order->updated_at->toISOString(),
            'note' => 'Order is being prepared.',
        ];
    }

    if ($order->shipped_at) {
        $timeline[] = [
            'status' => 'shipped',
            'timestamp' => $order->shipped_at->toISOString(),
            'note' => 'Package has been shipped.',
        ];
    }

    if ($order->delivered_at) {
        $timeline[] = [
            'status' => 'delivered',
            'timestamp' => $order->delivered_at->toISOString(),
            'note' => 'Package delivered successfully.',
        ];
    }

    if ($order->cancelled_at) {
        $timeline[] = [
            'status' => 'cancelled',
            'timestamp' => $order->cancelled_at->toISOString(),
            'note' => $order->cancellation_reason ?? 'Order cancelled.',
        ];
    }

    return response()->json([
        'success' => true,
        'data' => [
            'current_status' => $order->status,
            'timeline' => $timeline,
        ],
    ]);
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "current_status": "shipped",
    "timeline": [
      { "status": "pending", "timestamp": "2025-05-25T10:00:00Z", "note": "Order placed successfully." },
      { "status": "paid", "timestamp": "2025-05-25T10:05:00Z", "note": "Payment confirmed." },
      { "status": "processing", "timestamp": "2025-05-25T11:00:00Z", "note": "Order is being prepared." },
      { "status": "shipped", "timestamp": "2025-05-25T14:00:00Z", "note": "Package has been shipped." }
    ]
  }
}
```

---

### 4. Featured Products Endpoint

**Endpoint:** `GET /api/products/featured`  
**Auth:** Public (no auth required)  
**Rate Limit:** 60 requests per minute

#### Add Route

**`routes/api.php`:**
```php
Route::get('/products/featured', [ProductController::class, 'featured']);
```

#### Add Controller Method

**`app/Http/Controllers/ProductController.php`:**
```php
public function featured()
{
    $products = Product::where('is_featured', true)
        ->where('is_active', true)
        ->with('category')
        ->inRandomOrder()
        ->limit(8)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $products,
    ]);
}
```

#### Frontend-suggested returns
- At least 8 products
- Sorted randomly (for variety on page load)
- Include `category` relationship

#### Database Setup

Add `is_featured` boolean column to products table (if not exists):
```php
// In a migration
Schema::table('products', function (Blueprint $table) {
    $table->boolean('is_featured')->default(false)->after('is_active');
});
```

---

### 5. Health Profile — Auto-create on Registration

**Problem:** `GET /api/user/health-profile` returns 404 if no profile exists.  
**Solution:** Create default empty health profile when user registers (pass `customer` role check).

#### Option A: Create in Register Method

**`app/Http/Controllers/AuthController.php`:**
```php
use App\Models\HealthProfile;

public function register(RegisterRequest $request)
{
    $user = User::create([...]);

    // Auto-create health profile
    HealthProfile::create(['user_id' => $user->id]);

    // ... rest (token generation, response)
}
```

#### Option B: Use Model `booted()` event

**`app/Models/User.php`:**
```php
protected static function booted()
{
    static::created(function ($user) {
        if ($user->role === 'customer') {
            $user->healthProfile()->create();
        }
    });
}

public function healthProfile()
{
    return $this->hasOne(HealthProfile::class);
}
```

#### Option C: Return Empty Default (if no auto-create)

**`app/Http/Controllers/HealthProfileController.php`:**
```php
public function show()
{
    $profile = HealthProfile::where('user_id', auth()->id())->first();

    if (!$profile) {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => null,
                'user_id' => auth()->id(),
                'age' => null,
                'weight' => null,
                'height' => null,
                'bmi' => null,
                'goals' => null,
                'allergies' => [],
                'dietary_type' => null,
                'activity_level' => null,
                'medical_conditions' => null,
                'daily_calorie_target' => null,
            ],
        ]);
    }

    return response()->json(['success' => true, 'data' => $profile]);
}
```

**Recommendation:** Use Option A (create during register) + Option C (fallback for existing users without profile) together.

---

### 6. Admin Bulk Update Products

**Endpoint:** `PUT /api/admin/products/bulk-update`  
**Auth:** Sanctum — `admin` role  
**Rate Limit:** 30 requests per minute

#### Add Route

**`routes/api.php`:**
```php
Route::put('/admin/products/bulk-update', [AdminProductController::class, 'bulkUpdate']);
```

#### Create FormRequest

```bash
php artisan make:request BulkUpdateProductsRequest
```

**`app/Http/Requests/BulkUpdateProductsRequest.php`:**
```php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1', 'max:100'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.updates' => ['required', 'array'],
            'products.*.updates.name' => ['sometimes', 'string', 'max:255'],
            'products.*.updates.price' => ['sometimes', 'numeric', 'min:0'],
            'products.*.updates.compare_at_price' => ['sometimes', 'numeric', 'nullable', 'min:0'],
            'products.*.updates.stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'products.*.updates.is_active' => ['sometimes', 'boolean'],
            'products.*.updates.is_featured' => ['sometimes', 'boolean'],
            'products.*.updates.category_id' => ['sometimes', 'integer', 'exists:categories,id'],
        ];
    }
}
```

#### Add Controller Method

**`app/Http/Controllers/Admin/ProductController.php`:**
```php
use App\Http\Requests\BulkUpdateProductsRequest;

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
```

#### Request Example
```json
{
  "products": [
    {
      "id": 1,
      "updates": { "price": 29.99, "is_featured": true }
    },
    {
      "id": 2,
      "updates": { "stock_quantity": 100, "is_active": true }
    }
  ]
}
```

#### Validation
- Max 100 products per request
- Each product must have valid `id` and `updates` object
- Each update field is optional (only update what's provided)

---

### 7. Admin Categories CRUD

**Missing entirely — need full CRUD.**

#### Routes

**`routes/api.php`:**
```php
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', AdminCategoryController::class);
});
```

#### Controller

```bash
php artisan make:controller Admin/CategoryController --resource
```

**`app/Http/Controllers/Admin/CategoryController.php`:**
```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function show($id)
    {
        $category = Category::with('children', 'parent')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category->load('children', 'parent'),
            'message' => 'Category created successfully.',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter++;
            }
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'data' => $category->fresh()->load('children', 'parent'),
            'message' => 'Category updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Prevent deleting if has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories. Remove children first.',
            ], 409);
        }

        // Prevent deleting if has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with products. Reassign products first.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
```

#### Request Examples

**Create:**
```json
POST /api/admin/categories
{
  "name": "Organic Vegetables",
  "description": "Fresh organic vegetables",
  "parent_id": null,
  "sort_order": 1,
  "is_active": true
}
```

**Update:**
```json
PUT /api/admin/categories/5
{
  "name": "Premium Organic Vegetables",
  "sort_order": 2
}
```

#### Validation Rules
- `name`: required on create, sometimes on update — max 255 chars
- `description`: nullable string
- `image_url`: nullable, must be valid URL
- `parent_id`: nullable, must exist in categories table
- `sort_order`: nullable integer, min 0
- `is_active`: nullable boolean
- Auto-generate `slug` from name (ensure uniqueness)
- Cannot delete if has children or products

---

## 🟡 MEDIUM PRIORITY

### 8. Product Reviews

**Endpoints:**
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/products/{id}/reviews` | Public | List reviews for a product |
| POST | `/api/products/{id}/reviews` | Customer | Add review |
| PUT | `/api/reviews/{id}` | Customer | Edit own review |
| DELETE | `/api/reviews/{id}` | Customer | Delete own review |

**Database Migration:**
```php
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->tinyInteger('rating')->unsigned(); // 1-5
    $table->text('comment')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'product_id']); // one review per user per product
});
```

**Controller (simplified):**
```php
public function index($productId)
{
    $reviews = Review::where('product_id', $productId)
        ->with('user:id,name')
        ->latest()
        ->paginate(10);

    return response()->json([
        'success' => true,
        'data' => $reviews->items(),
        'meta' => [
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
            'last_page' => $reviews->lastPage(),
            'avg_rating' => (float) Review::where('product_id', $productId)->avg('rating'),
            'total_reviews' => Review::where('product_id', $productId)->count(),
        ],
    ]);
}
```

---

### 9. Admin Users CRUD

**Endpoints:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/users` | List all users (paginated, filterable by role) |
| GET | `/api/admin/users/{id}` | Get user details |
| PUT | `/api/admin/users/{id}` | Update user (name, email, role) |
| DELETE | `/api/admin/users/{id}` | Soft-delete user |

**Controller (simplified):**
```php
public function index(Request $request)
{
    $query = User::query();

    if ($request->role) {
        $query->where('role', $request->role);
    }

    return response()->json([
        'success' => true,
        'data' => $query->paginate(15)->items(),
    ]);
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'name' => ['sometimes', 'string', 'max:255'],
        'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
        'role' => ['sometimes', Rule::in(['admin', 'vendor', 'customer'])],
    ]);

    $user->update($validated);

    return response()->json(['success' => true, 'data' => $user]);
}

public function destroy($id)
{
    $user = User::findOrFail($id);

    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot delete your own account.',
        ], 409);
    }

    $user->delete();

    return response()->json(['success' => true, 'message' => 'User deleted.']);
}
```

---

### 10. Vendor Product Analytics

**Endpoint:** `GET /api/vendor/products/{id}/analytics`  
**Auth:** Sanctum — `vendor` role

```php
public function analytics($id)
{
    $product = Product::where('vendor_id', auth()->id())->findOrFail($id);

    $totalSold = OrderItem::where('product_id', $product->id)
        ->whereHas('order', fn($q) => $q->whereIn('status', ['delivered', 'shipped']))
        ->sum('quantity');

    $revenue = OrderItem::where('product_id', $product->id)
        ->whereHas('order', fn($q) => $q->whereIn('status', ['delivered']))
        ->sum(\DB::raw('quantity * unit_price'));

    return response()->json([
        'success' => true,
        'data' => [
            'total_sold' => (int) $totalSold,
            'total_revenue' => (float) $revenue,
            'current_stock' => $product->stock_quantity,
            'views' => 0, // placeholder for future
            'orders_last_30_days' => OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->sum('quantity'),
        ],
    ]);
}
```

---

### 11. Discount/Coupon CRUD

**Endpoints:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/discounts` | List all coupons |
| POST | `/api/admin/discounts` | Create coupon |
| GET | `/api/admin/discounts/{id}` | Get coupon details |
| PUT | `/api/admin/discounts/{id}` | Update coupon |
| DELETE | `/api/admin/discounts/{id}` | Delete coupon |
| POST | `/api/customer/orders/apply-discount` | Apply discount code at checkout |

**Migration:**
```php
Schema::create('discounts', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique();
    $table->enum('type', ['percentage', 'fixed']);    // percentage off or fixed amount
    $table->decimal('value', 10, 2);                   // e.g., 10.00 = 10% or $10
    $table->decimal('min_order_amount', 10, 2)->nullable();
    $table->integer('max_uses')->nullable();            // null = unlimited
    $table->integer('used_count')->default(0);
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

### 12. Change Password Endpoint

**Endpoint:** `PUT /api/auth/change-password`  
**Auth:** Sanctum (any authenticated user)

```php
public function changePassword(Request $request)
{
    $validated = $request->validate([
        'current_password' => ['required', 'current_password'],
        'new_password' => ['required', 'confirmed', Password::min(8)],
    ]);

    auth()->user()->update([
        'password' => Hash::make($validated['new_password']),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully.',
    ]);
}
```

---

### 13. Update User Profile

**Endpoint:** `PUT /api/user/profile`  
**Auth:** Sanctum (any authenticated user)

```php
public function updateProfile(Request $request)
{
    $validated = $request->validate([
        'name' => ['sometimes', 'string', 'max:255'],
        'email' => ['sometimes', 'email', Rule::unique('users')->ignore(auth()->id())],
        'phone' => ['nullable', 'string', 'max:20'],
        'avatar_url' => ['nullable', 'url'],
    ]);

    auth()->user()->update($validated);

    return response()->json([
        'success' => true,
        'data' => auth()->user()->fresh(),
    ]);
}
```

---

### 14. Get Products by IDs (Bulk)

**Endpoint:** `GET /api/products/bulk?ids=1,2,3,4`  
**Auth:** Public

```php
public function bulk(Request $request)
{
    $ids = explode(',', $request->ids);

    $validated = collect($ids)->filter(fn($id) => is_numeric($id))->values()->toArray();

    if (count($validated) > 50) {
        return response()->json([
            'success' => false,
            'message' => 'Maximum 50 products per request.',
        ], 400);
    }

    $products = Product::whereIn('id', $validated)
        ->where('is_active', true)
        ->with('category')
        ->get();

    return response()->json(['success' => true, 'data' => $products]);
}
```

---

## 🟢 LOW PRIORITY

### 15. Wishlist CRUD

**Migration:**
```php
Schema::create('wishlists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'product_id']);
});
```

**Routes:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customer/wishlist', [WishlistController::class, 'index']);
    Route::post('/customer/wishlist/{product}', [WishlistController::class, 'add']);
    Route::delete('/customer/wishlist/{product}', [WishlistController::class, 'remove']);
});
```

---

### 16. Notifications

**Migration:**
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('type');     // 'order_status', 'low_stock', 'promotion'
    $table->string('title');
    $table->text('body')->nullable();
    $table->json('data')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamps();
});
```

**Endpoints:**
- `GET /api/notifications` — list user notifications (paginated, latest first)
- `PUT /api/notifications/{id}/read` — mark as read
- `PUT /api/notifications/read-all` — mark all as read

---

### 17. App Settings

**Endpoint:** `GET/PUT /api/admin/settings`  
**Auth:** Sanctum — `admin` role

```php
// Use a simple key-value settings table or config
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value')->nullable();
});

// GET: return all settings as object
public function index()
{
    $settings = Setting::pluck('value', 'key');
    return response()->json(['success' => true, 'data' => $settings]);
}

// PUT: accept { "key": "value", ... }
public function update(Request $request)
{
    foreach ($request->all() as $key => $value) {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
    return response()->json(['success' => true, 'message' => 'Settings updated.']);
}
```

---

### 18. Nutrition Info Lookup

**Endpoint:** `GET /api/products/nutrition?q=vitamin+c`  
**Auth:** Public

```php
public function nutrition(Request $request)
{
    $query = $request->q;

    $products = Product::where('is_active', true)
        ->whereNotNull('nutrition_data')
        ->when($query, fn($q) => $q->where('nutrition_data', 'like', "%{$query}%"))
        ->limit(20)
        ->get(['id', 'name', 'nutrition_data']);

    return response()->json(['success' => true, 'data' => $products]);
}
```

---

### 19. AI Nutrition Breakdown for Product

**Endpoint:** `GET /api/customer/ai/nutrition/{productId}`  
**Auth:** Sanctum — `customer` role

```php
public function nutritionBreakdown($productId)
{
    $product = Product::findOrFail($productId);

    if (!$product->nutrition_data) {
        return response()->json([
            'success' => false,
            'message' => 'No nutrition data available for this product.',
        ], 404);
    }

    // Generate AI-style breakdown from stored nutrition_data
    $nutrition = $product->nutrition_data;
    $breakdown = [
        'product_name' => $product->name,
        'serving_size' => $nutrition['serving_size'] ?? '100g',
        'calories' => $nutrition['calories'] ?? 0,
        'macros' => [
            'protein' => $nutrition['protein'] ?? 0,
            'carbs' => $nutrition['carbs'] ?? 0,
            'fat' => $nutrition['fat'] ?? 0,
            'fiber' => $nutrition['fiber'] ?? 0,
        ],
        'daily_value_percentages' => [
            'vitamin_a' => $nutrition['vitamin_a'] ?? 0,
            'vitamin_c' => $nutrition['vitamin_c'] ?? 0,
            'calcium' => $nutrition['calcium'] ?? 0,
            'iron' => $nutrition['iron'] ?? 0,
        ],
        'dietary_fit' => [
            'is_vegetarian' => $nutrition['is_vegetarian'] ?? false,
            'is_vegan' => $nutrition['is_vegan'] ?? false,
            'is_gluten_free' => $nutrition['is_gluten_free'] ?? false,
            'is_keto_friendly' => $nutrition['is_keto_friendly'] ?? false,
        ],
        'health_notes' => $nutrition['health_notes'] ?? 'No health notes available.',
    ];

    return response()->json(['success' => true, 'data' => $breakdown]);
}
```

---

## Implementation Order

| Order | API | Effort | Impact |
|-------|-----|--------|--------|
| 1 | Featured Products endpoint | Low | High — homepage needs it |
| 2 | FormRequest refactor (auth) | Low | Medium — code quality |
| 3 | Health Profile auto-create | Low | Medium — UX improvement |
| 4 | Admin Categories CRUD | Medium | High — admin panel needs it |
| 5 | Order Cancel endpoint | Low | High — frontend needs it |
| 6 | Order Tracking endpoint | Low | High — frontend needs it |
| 7 | Admin Bulk Update Products | Low | Medium — admin efficiency |
| 8 | Change Password endpoint | Low | Medium — security |
| 9 | Update Profile endpoint | Low | Medium — user settings |
| 10 | Product Reviews | Medium | High — e-commerce standard |
| 11 | Admin Users CRUD | Low | Medium — admin management |
| 12 | Bulk Products endpoint | Low | Low — comparison feature |
| 13 | Vendor Analytics | Low | Medium — vendor dashboard |
| 14 | Discount/Coupon system | Medium | Low — checkout enhancement |
| 15 | Notifications | Medium | Low — nice to have |
| 16 | Wishlist | Low | Low — nice to have |
| 17 | Settings | Low | Low — nice to have |
| 18 | Nutrition endpoints | Low | Low — nice to have |

---

## Frontend API Client Endpoints (Already Built)

The Next.js frontend already has these API modules ready. Each `endpoint` in `src/lib/api/` maps to the APIs above:

| API Module | Missing APIs Covered |
|------------|---------------------|
| `products.ts` | `featured()`, `bulkUpdate()`, `vendorProducts()` |
| `orders.ts` | `cancel()`, `track()` |
| `health.ts` | `getProfile()`, `updateProfile()` |
| `admin.ts` | `categories()` CRUD, `users()` CRUD |
| `auth.ts` | FormRequest-ready (login/register methods) |

The adapters in `src/lib/adapters/` handle transforming API response data into the format expected by the frontend components.

---

> **Note:** This guide assumes Laravel project structure with Sanctum auth, role middleware, and Eloquent models. Adjust namespace paths (`App\Http\Controllers\Admin\*`) based on actual project structure.
