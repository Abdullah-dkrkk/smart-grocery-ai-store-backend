# Smart Grocery AI Store — Backend API Documentation

> **Base URL:** `http://localhost:8000/api`  
> **Auth:** Laravel Sanctum (Bearer Token)  
> **Response Format:** `{ "success": true|false, "data": ..., "message": "..." }`  
> **Pagination Meta:** `{ "current_page", "per_page", "total", "last_page", "from", "to" }`

---

## Table of Contents

1. [Auth API](#1-auth-api)
2. [Products API](#2-products-api)
3. [Health Profile API](#3-health-profile-api)
4. [AI Assistant API](#4-ai-assistant-api)
5. [Customer Cart API](#5-customer-cart-api)
6. [Customer Orders API](#6-customer-orders-api)
7. [Admin Products API](#7-admin-products-api)
8. [Admin Orders API](#8-admin-orders-api)
9. [Admin Dashboard API](#9-admin-dashboard-api)
10. [Vendor Products API](#10-vendor-products-api)
11. [Database Schema Overview](#11-database-schema-overview)
12. [Missing APIs / Recommendations](#12-missing-apis--recommendations)

---

## 1. Auth API

**Tag:** `Auth`  
**Role:** Public (register, login, forgot/reset password) + Authenticated (logout, me)

### 1.1 POST `/auth/register`
Register a new customer account.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Full name of user |
| `email` | string | ✅ | Valid email address |
| `password` | string | ✅ | Min 8 characters |
| `password_confirmation` | string | ✅ | Must match password |

- **Rate Limit:** 10 requests per minute
- **Success Response (201):** `{ user, token }`

### 1.2 POST `/auth/login`
Authenticate user and return Sanctum token.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | ✅ | User email |
| `password` | string | ✅ | User password |

- **Rate Limit:** 15 requests per minute
- **Success Response (200):** `{ user, token }`
- **Error (401):** Invalid credentials

### 1.3 POST `/auth/logout` 🔒
Revoke current access token.

- **Auth:** Sanctum Token required
- **Success Response (200):** `{ message: "Logout successful" }`

### 1.4 POST `/auth/forgot-password`
Send password reset link.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | ✅ | Registered email |

- **Rate Limit:** 5 requests per minute
- **Note:** Currently a placeholder — no actual email sending implemented.

### 1.5 POST `/auth/reset-password`
Reset password using token.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | ✅ | Reset token from email |
| `email` | string | ✅ | User email |
| `password` | string | ✅ | New password (min 8 chars) |
| `password_confirmation` | string | ✅ | Confirm new password |

- **Rate Limit:** 5 requests per minute
- **Note:** Currently a placeholder — no actual token verification.

### 1.6 GET `/auth/me` 🔒
Get authenticated user's profile.

- **Auth:** Sanctum Token required
- **Rate Limit:** 30 requests per minute
- **Success Response (200):** Returns full user object

---

## 2. Products API

**Tag:** `Products`  
**Role:** Public (no auth required)

### 2.1 GET `/products`
List all active products with filtering.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `page` | integer | ❌ | Page number (default: 1) |
| `per_page` | integer | ❌ | Items per page (default: 15) |
| `category_id` | integer | ❌ | Filter by category |
| `search` | string | ❌ | Search in name/description |
| `sort_by` | string | ❌ | `price`, `name`, or `created_at` |
| `sort_dir` | string | ❌ | `asc` or `desc` |

- **Rate Limit:** 60 requests per minute
- **Loads:** `category`, `vendor` relationships

### 2.2 GET `/products/{id}`
Get single product details.

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | ✅ | Product ID |

- **Loads:** `category`, `vendor` relationships

### 2.3 GET `/products/categories`
List all active parent categories with children.

- **Returns:** Category tree (parent → children)
- **Ordered by:** `sort_order`

### 2.4 GET `/products/search`
AI-powered product search.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `q` | string | ✅ | Search query (min 2 chars) |
| `dietary_type` | string | ❌ | Filter by dietary type |

- **Returns:** `{ products[], total_results, query }`
- **Limit:** Max 20 results

---

## 3. Health Profile API

**Tag:** `Health Profile`  
**Role:** `customer` (authenticated)

### 3.1 GET `/user/health-profile` 🔒
Get current user's health profile.

- **Auth:** Sanctum Token required
- **Error (404):** Profile not found

### 3.2 PUT `/user/health-profile` 🔒
Create or update health profile.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `age` | integer | ❌ | 1–150 |
| `weight` | float | ❌ | 1–500 kg |
| `height` | float | ❌ | 1–300 cm |
| `goals` | string | ❌ | e.g., `weight_loss`, `muscle_gain` |
| `allergies` | array | ❌ | e.g., `["peanuts", "shellfish"]` |
| `dietary_type` | string | ❌ | e.g., `vegetarian`, `vegan` |
| `activity_level` | string | ❌ | e.g., `sedentary`, `moderate`, `active` |
| `medical_conditions` | string | ❌ | Any medical notes |
| `daily_calorie_target` | integer | ❌ | 500–10000 |

- **Auto-calculates:** BMI from weight & height
- **Uses:** `updateOrCreate` — one profile per user

---

## 4. AI Assistant API

**Tag:** `AI Assistant`  
**Role:** `customer` (authenticated)

### 4.1 POST `/customer/ai/ask` 🔒
Ask AI a health/nutrition question.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `message` | string | ✅ | Question (2–2000 chars) |
| `context` | string | ❌ | Context type (e.g., `product_recommendation`) |

- **Rate Limit:** 20 requests per minute
- **Returns:** `{ response, suggested_products[], conversation_id }`
- **Note:** Uses keyword-based mock responses (not real AI yet)

### 4.2 GET `/customer/ai/suggestions` 🔒
Get AI-suggested questions based on health profile.

- **Returns:** Array of 5 suggested questions
- **Personalizes** based on goals, allergies, dietary type

### 4.3 POST `/customer/ai/identify` 🔒
Identify a product from an image upload.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `image` | file | ✅ | JPEG/PNG/WebP (max 5MB) |

- **Rate Limit:** 10 requests per minute
- **Content-Type:** `multipart/form-data`
- **Returns:** `{ identified_product, confidence, nutrition_summary, healthier_alternatives[], image_url }`
- **Note:** Uses filename keyword matching (not real AI vision)

### 4.4 GET `/customer/ai/diet-plan` 🔒
Get personalized diet plan.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `duration` | integer | ❌ | Number of days (default: 7) |

- **Rate Limit:** 10 requests per minute
- **Requires:** Health profile must exist
- **Returns:** `{ plan, daily_meals[], total_calories, duration_days }`

### 4.5 GET `/customer/ai/chat/history` 🔒
Get paginated chat history.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `page` | integer | ❌ | Page number (default: 1) |
| `per_page` | integer | ❌ | Items per page (default: 20) |

- **Rate Limit:** 30 requests per minute
- **Orders by:** Latest first

---

## 5. Customer Cart API

**Tag:** `Customer Cart`  
**Role:** `customer` (authenticated)

### 5.1 GET `/customer/cart` 🔒
Get all cart items with subtotal.

- **Returns:** `{ items[], subtotal, item_count }`
- **Loads:** `product` relationship

### 5.2 POST `/customer/cart/add` 🔒
Add item to cart (or update quantity).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_id` | integer | ✅ | Must exist in products |
| `quantity` | integer | ✅ | Min 1 |

- **Uses:** `updateOrCreate` — unique per user+product
- **Checks:** Product must be in stock
- **Loads:** `product` relationship

### 5.3 DELETE `/customer/cart/{id}` 🔒
Remove item from cart.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ | Cart item ID |

- **Scoped to:** Current user only
- **Error (404):** Cart item not found

---

## 6. Customer Orders API

**Tag:** `Customer Orders`  
**Role:** `customer` (authenticated)

### 6.1 POST `/customer/orders/checkout` 🔒
Place an order from cart items.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shipping_address` | string | ✅ | Shipping address |
| `billing_address` | string | ❌ | Defaults to shipping address |
| `shipping_phone` | string | ✅ | Contact number |
| `payment_method` | string | ✅ | `credit_card`, `debit_card`, or `cash_on_delivery` |
| `notes` | string | ❌ | Order notes |
| `discount_code` | string | ❌ | Discount code (not yet implemented) |

- **Process:**
  1. Validates stock for all items
  2. Creates order with auto-generated order number
  3. Creates order items from cart
  4. Decrements stock quantities
  5. Clears user's cart
  6. Calculates: subtotal + 10% tax + free shipping (>$50) or $5.99
- **Error (400):** Cart is empty or product out of stock

### 6.2 GET `/customer/orders` 🔒
Get paginated order history.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `status` | string | ❌ | Filter by status |

- **Paginated:** 15 per page
- **Loads:** `items.product`

### 6.3 GET `/customer/orders/{id}` 🔒
Get single order details.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ | Order ID |

- **Scoped to:** Current user only
- **Loads:** `items.product`

---

## 7. Admin Products API

**Tag:** `Admin Products`  
**Role:** `admin` (authenticated)

### 7.1 GET `/admin/products` 🔒
List all products (including inactive).

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `page` | integer | ❌ | Page number |
| `per_page` | integer | ❌ | Items per page (default: 15) |
| `vendor_id` | integer | ❌ | Filter by vendor |
| `category_id` | integer | ❌ | Filter by category |
| `is_active` | boolean | ❌ | Filter by status |
| `search` | string | ❌ | Search in name, description, SKU |

- **Loads:** `category`, `vendor` relationships

### 7.2 POST `/admin/products` 🔒
Create a new product.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Product name |
| `description` | string | ❌ | Product description |
| `price` | float | ✅ | Price (min 0) |
| `compare_at_price` | float | ❌ | Original price (must be >= price) |
| `category_id` | integer | ✅ | Must exist |
| `vendor_id` | integer | ❌ | Vendor user ID |
| `stock_quantity` | integer | ✅ | Min 0 |
| `min_stock_threshold` | integer | ❌ | Low stock alert threshold |
| `is_active` | boolean | ❌ | Default: true |
| `is_featured` | boolean | ❌ | Default: false |
| `nutrition_data` | object | ❌ | JSON object with nutrition info |
| `sku` | string | ❌ | Unique SKU |
| `weight_kg` | float | ❌ | Weight in kg |
| `image_url` | string | ❌ | Product image URL |

- **Auto-generates:** `slug` from name

### 7.3 GET `/admin/products/{id}` 🔒
Get product details (any product).

### 7.4 PUT `/admin/products/{id}` 🔒
Update a product.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| All product fields | various | ❌ | Same as create, all optional |

### 7.5 DELETE `/admin/products/{id}` 🔒
Soft-delete a product.

### 7.6 POST `/admin/products/upload-image` 🔒
Upload product image.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `image` | file | ✅ | JPEG/PNG/WebP (max 2MB) |

- **Content-Type:** `multipart/form-data`
- **Returns:** `{ image_url }`

---

## 8. Admin Orders API

**Tag:** `Admin Orders`  
**Role:** `admin` (authenticated)

### 8.1 GET `/admin/orders` 🔒
List all orders with filtering.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `page` | integer | ❌ | Page number |
| `status` | string | ❌ | Filter by status |
| `payment_status` | string | ❌ | Filter by payment status |
| `search` | string | ❌ | Search by order number, customer name/email |

- **Loads:** `user`, `items.product`

### 8.2 GET `/admin/orders/{id}` 🔒
Get order details.

### 8.3 PUT `/admin/orders/{id}/status` 🔒
Update order status.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | ✅ | `pending`, `processing`, `shipped`, `delivered`, `cancelled` |
| `notes` | string | ❌ | Status update notes |

- **Auto-sets timestamps:** `paid_at`, `shipped_at`, `delivered_at`, `cancelled_at`

---

## 9. Admin Dashboard API

**Tag:** `Admin Dashboard`  
**Role:** `admin` (authenticated)

### 9.1 GET `/admin/dashboard/overview` 🔒
Get dashboard overview statistics.

- **Returns:** `{ total_revenue, total_orders, total_products, total_customers, total_vendors, orders_by_status, revenue_by_period (today/this_week/this_month), recent_orders[], low_stock_products[] }`

### 9.2 GET `/admin/dashboard/trends` 🔒
Get revenue and order trends.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `period` | string | ❌ | `7`, `30`, or `90` days (default: 30) |

- **Returns:** `{ revenue_trend, orders_trend, top_products[], top_customers[] }`

---

## 10. Vendor Products API

**Tag:** `Vendor Products`  
**Role:** `vendor` (authenticated, scoped to own products only)

### 10.1 GET `/vendor/products` 🔒
List vendor's own products.

| Query Param | Type | Required | Description |
|-------------|------|----------|-------------|
| `page` | integer | ❌ | Page number |
| `per_page` | integer | ❌ | Items per page (default: 15) |
| `category_id` | integer | ❌ | Filter by category |
| `is_active` | boolean | ❌ | Filter by status |
| `search` | string | ❌ | Search in name/description |

- **Auto-filtered to:** Current vendor's ID

### 10.2 POST `/vendor/products` 🔒
Create a product (auto-assigned to current vendor).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Product name |
| `description` | string | ❌ | Description |
| `price` | float | ✅ | Price |
| `compare_at_price` | float | ❌ | Original price |
| `category_id` | integer | ✅ | Must exist |
| `stock_quantity` | integer | ✅ | Min 0 |
| `is_active` | boolean | ❌ | Default: true |
| `nutrition_data` | object | ❌ | JSON nutrition data |
| `sku` | string | ❌ | Unique SKU |
| `weight_kg` | float | ❌ | Weight |

- **Auto-sets:** `vendor_id = current_user.id`

### 10.3 GET `/vendor/products/{id}` 🔒
Get own product details.

### 10.4 PUT `/vendor/products/{id}` 🔒
Update own product.

### 10.5 DELETE `/vendor/products/{id}` 🔒
Soft-delete own product.

### 10.6 POST `/vendor/products/upload-image` 🔒
Upload product image (same as admin).

### 10.7 GET `/vendor/products/stats` 🔒
Get vendor's product statistics.

- **Returns:** `{ total_products, active_products, inactive_products, low_stock_products, out_of_stock_products, total_revenue }`

---

## 11. Database Schema Overview

### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| email | string | Unique |
| password | string | Hashed |
| role | string | `admin`, `vendor`, `customer` |
| timestamps | datetime | |
| deleted_at | datetime | Soft deletes |

### categories
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| slug | string | Unique |
| description | text | Nullable |
| image_url | string | Nullable |
| parent_id | bigint | FK → categories (nullable) |
| sort_order | integer | Default: 0 |
| is_active | boolean | Default: true |

### products
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| slug | string | Unique |
| description | text | Nullable |
| price | decimal(10,2) | |
| compare_at_price | decimal(10,2) | Nullable |
| image_url | string | Nullable |
| category_id | bigint | FK → categories |
| vendor_id | bigint | FK → users (nullable) |
| stock_quantity | integer | Default: 0 |
| min_stock_threshold | integer | Default: 5 |
| is_active | boolean | Default: true |
| is_featured | boolean | Default: false |
| nutrition_data | json | Nullable |
| sku | string | Nullable, unique |
| weight_kg | decimal(8,3) | Nullable |

### product_images
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| product_id | bigint | FK → products |
| image_url | string | |
| variation_type | string | Nullable |
| alt_text | string | Nullable |
| is_primary | boolean | Default: false |
| sort_order | integer | Default: 0 |

### cart_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| product_id | bigint | FK → products |
| quantity | integer | Default: 1 |
| | | Unique: (user_id, product_id) |

### orders
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| order_number | string | Unique |
| subtotal | decimal(10,2) | |
| tax_amount | decimal(10,2) | Default: 0 |
| shipping_cost | decimal(10,2) | Default: 0 |
| discount_amount | decimal(10,2) | Default: 0 |
| total_amount | decimal(10,2) | |
| status | string | Default: pending |
| payment_method | string | Nullable |
| payment_status | string | Default: pending |
| shipping_address | text | |
| billing_address | text | Nullable |
| shipping_phone | string | Nullable |
| notes | text | Nullable |
| paid_at | datetime | Nullable |
| shipped_at | datetime | Nullable |
| delivered_at | datetime | Nullable |
| cancelled_at | datetime | Nullable |
| cancellation_reason | text | Nullable |

### order_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| order_id | bigint | FK → orders |
| product_id | bigint | FK → products |
| product_name | string | Snapshot at order time |
| quantity | integer | |
| unit_price | decimal(10,2) | |
| subtotal | decimal(10,2) | |

### health_profiles
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| age | integer | Nullable |
| weight | float | Nullable |
| height | float | Nullable |
| bmi | float | Nullable, auto-calculated |
| goals | string | Nullable |
| allergies | json | Nullable, array of strings |
| dietary_type | string | Nullable |
| activity_level | string | Nullable |
| medical_conditions | text | Nullable |
| daily_calorie_target | integer | Nullable |

### chat_messages
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| message | text | |
| response | text | Nullable |
| type | string | Default: text |
| context | string | Nullable |
| metadata | json | Nullable |
| image_url | string | Nullable |
| response_time_ms | float | Nullable |

---

## 12. Missing APIs / Recommendations

Based on the `.cursorrules` and frontend `.cursorrules` analysis, the following APIs are missing or incomplete:

### 🔴 High Priority Missing APIs

| # | API Endpoint | Method | Description | Reason |
|---|-------------|--------|-------------|--------|
| 1 | `/api/auth/register` should use FormRequest | POST | Refactor to use dedicated RegisterRequest | .cursorrules mandates FormRequest |
| 2 | `/api/auth/login` should use FormRequest | POST | Refactor to use dedicated LoginRequest | .cursorrules mandates FormRequest |
| 3 | `/api/customer/orders/{id}/cancel` | PUT | Cancel an order (customer) | Frontend needs cancel functionality |
| 4 | `/api/customer/orders/{id}/track` | GET | Track order status with timeline | Customer needs tracking view |
| 5 | `/api/products/featured` | GET | Get featured products for homepage | Frontend homepage needs this |
| 6 | `/api/user/health-profile` (GET) returns 404 if no profile | GET | Should create empty profile by default | Better UX |
| 7 | `/api/admin/products/bulk-update` | PUT | Bulk update products | Admin needs bulk operations |
| 8 | `/api/admin/categories` CRUD | All | Admin category management | Missing entirely from backend |

### 🟡 Medium Priority Missing APIs

| # | API Endpoint | Method | Description | Reason |
|---|-------------|--------|-------------|--------|
| 9 | `/api/products/{id}/reviews` | GET | Get product reviews | Not implemented |
| 10 | `/api/admin/users` | GET | List all users | Admin user management missing |
| 11 | `/api/admin/users/{id}` | GET/PUT/DELETE | Manage specific user | Admin user management missing |
| 12 | `/api/vendor/products/{id}/analytics` | GET | Per-product analytics for vendor | Vendor dashboard needs this |
| 13 | `/api/admin/discounts` | CRUD | Discount/coupon management | Checkout has discount_code but no backend |
| 14 | `/api/auth/change-password` | PUT | Change password while logged in | Security best practice |
| 15 | `/api/user/profile` | PUT | Update user profile (name, email) | User settings page |
| 16 | `/api/products/bulk` | GET | Get multiple products by IDs array | Product comparison/wishlist |

### 🟢 Low Priority Nice-to-Have APIs

| # | API Endpoint | Method | Description | Reason |
|---|-------------|--------|-------------|--------|
| 17 | `/api/customer/wishlist` | CRUD | Wishlist functionality | E-commerce standard |
| 18 | `/api/notifications` | GET/PUT | User notifications | Dashboard notification bell |
| 19 | `/api/admin/settings` | GET/PUT | Application settings | Global config |
| 20 | `/api/products/nutrition` | GET | Nutrition info lookup | Standalone nutrition search |
| 21 | `/api/customer/ai/nutrition/{productId}` | GET | AI nutrition breakdown for a product | Enhanced product detail |
