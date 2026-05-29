# SmartGrocery AI Store — Dashboard Roles & API Specification

> **Purpose:** Document all dashboard roles, their permissions, page-level access, and the complete API surface required for each role. Share this with the Laravel backend team so they know exactly what to build.
>
> **Auth:** Laravel Sanctum (Bearer Token)  
> **Base URL:** `/api`  
> **Response Format:** `{ "success": bool, "data": T, "message": "..." }`  
> **Pagination:** `{ "current_page", "per_page", "total", "last_page", "from", "to" }`

---

## Table of Contents

1. [Role Overview](#1-role-overview)
2. [User Role](#2-user-role)
3. [Vendor Role](#3-vendor-role)
4. [Nutritionist Role](#4-nutritionist-role)
5. [Super Admin Role](#5-super-admin-role)
6. [Shared / Cross-Role APIs](#6-shared--cross-role-apis)
7. [Database Schema — New & Modified Tables](#7-database-schema--new--modified-tables)

---

## 1. Role Overview

| Role | Slug | Dashboard Prefix | Description |
|------|------|-----------------|-------------|
| User | `user` | `/dashboard?role=user` | End customer who browses, orders, and manages their account |
| Vendor | `vendor` | `/dashboard?role=vendor` | Seller who manages products, inventory, and orders |
| Nutritionist | `nutritionist` | `/dashboard?role=nutritionist` | Health expert who manages clients, meal plans, consultations |
| Super Admin | `super-admin` | `/dashboard?role=super-admin` | Platform overseer with full system access |

### 1.1 users.role column

The `users` table already has a `role` column using `string` (or enum). It must support these values:

```
'customer' | 'vendor' | 'nutritionist' | 'admin'
```

Map:
- `customer` → **User** role
- `vendor` → **Vendor** role
- `nutritionist` → **Nutritionist** role
- `admin` → **Super Admin** role

---

## 2. User Role

**Dashboard path:** `/dashboard?role=user`  
**DB role value:** `customer`

The User is the primary end customer who shops on the platform. Their dashboard is focused on order management, account settings, and nutrition tracking.

### 2.1 Pages / Sections

| # | Section | Page | Description |
|---|---------|------|-------------|
| 1 | Main | Overview | Summary of orders, pending deliveries, spending, recommendations |
| 2 | Main | My Orders | Full order history with status tracking |
| 3 | Main | Wishlist | Saved/favorited products |
| 4 | Main | Reviews | Product reviews written by the user |
| 5 | Account | My Profile | Name, email, password, avatar |
| 6 | Account | Addresses | Saved shipping/billing addresses |
| 7 | Account | Payment Methods | Saved payment cards/methods |
| 8 | Nutrition | Nutrition Plans | Personalized meal/diet plans from assigned nutritionist |

### 2.2 Permissions (What User Can Do)

- View and edit own profile
- Place and cancel orders
- Track order status
- View order history
- Add/remove wishlist items
- Write and edit product reviews
- Manage saved addresses (CRUD)
- Manage saved payment methods (CRUD)
- View assigned nutrition plans
- View own health profile
- Ask AI health/nutrition questions

### 2.3 Required APIs

#### 2.3.1 Auth APIs (already exist)

| Method | Endpoint | Notes |
|--------|----------|-------|
| POST | `/auth/register` | Register with role=`customer` |
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Get current user profile |
| PUT | `/auth/me` | Update profile (name, email, avatar) |
| PUT | `/auth/change-password` | Change password |

#### 2.3.2 Dashboard Overview

```
GET /customer/dashboard/overview
```
**Auth:** Sanctum (role: customer)  
**Response:**
```json
{
  "success": true,
  "data": {
    "total_orders": 24,
    "pending_deliveries": 3,
    "reviews_given": 8,
    "total_spent": 1284.00,
    "recent_orders": [
      {
        "id": 1,
        "order_number": "ORD-4821",
        "total_amount": 89.50,
        "status": "delivered",
        "item_count": 5,
        "created_at": "2 hours ago"
      }
    ],
    "upcoming_deliveries": [
      {
        "id": 1,
        "scheduled_date": "2026-05-30 14:00",
        "items_summary": "Fresh produce, dairy, bread",
        "status": "confirmed"
      }
    ],
    "recommended_products": [
      {
        "id": 1,
        "name": "Organic Avocados",
        "image_url": "...",
        "discount_percent": 15
      }
    ]
  }
}
```

#### 2.3.3 Orders

```
GET /customer/orders
```
**Query Params:** `page`, `per_page`, `status` (pending|processing|shipped|delivered|cancelled)  
**Auth:** Sanctum (role: customer)  
**Response:** Paginated list of orders with items.

```
GET /customer/orders/{id}
```
**Auth:** Sanctum (role: customer, scoped to own)  
**Response:** Single order with items, status timeline.

```
PUT /customer/orders/{id}/cancel
```
**Auth:** Sanctum (role: customer, scoped to own, only if status=pending)  
**Body:** `{ "reason": "string" }`  
**Response:** Updated order.

#### 2.3.4 Wishlist

```
GET /customer/wishlist
```
**Auth:** Sanctum (role: customer)  
**Response:** `{ "data": [Product] }`

```
POST /customer/wishlist
```
**Body:** `{ "product_id": int }`  
**Auth:** Sanctum (role: customer)  
**Response:** `{ "wishlisted": true }`

```
DELETE /customer/wishlist/{product_id}
```
**Auth:** Sanctum (role: customer)  
**Response:** `{ "wishlisted": false }`

#### 2.3.5 Reviews

```
GET /customer/reviews
```
**Auth:** Sanctum (role: customer)  
**Query:** `page`, `per_page`  
**Response:** Paginated list of user's reviews.

```
POST /customer/reviews
```
**Body:** `{ "product_id": int, "rating": float (0.5-5), "text": "string" }`  
**Auth:** Sanctum (role: customer, must have purchased product)  
**Response:** Created review.

```
PUT /customer/reviews/{id}
```
**Body:** `{ "rating": float, "text": "string" }`  
**Auth:** Sanctum (role: customer, scoped to own)

```
DELETE /customer/reviews/{id}
```
**Auth:** Sanctum (role: customer, scoped to own)

#### 2.3.6 Addresses

```
GET /customer/addresses
POST /customer/addresses
PUT /customer/addresses/{id}
DELETE /customer/addresses/{id}
```
**Body (POST/PUT):** `{ "label": "Home", "street": "string", "city": "string", "state": "string", "zip": "string", "is_default": bool }`  
**Auth:** Sanctum (role: customer, scoped to own)

#### 2.3.7 Payment Methods

```
GET /customer/payment-methods
POST /customer/payment-methods
DELETE /customer/payment-methods/{id}
```
**Body (POST):** `{ "card_number": "string", "card_holder": "string", "expiry_month": int, "expiry_year": int, "is_default": bool }`  
**Note:** Store only last 4 digits + expiry. Never store full CVV.

#### 2.3.8 Nutrition Plans

```
GET /customer/nutrition-plans
```
**Auth:** Sanctum (role: customer)  
**Response:** List of assigned nutrition plans from nutritionist(s).

```
GET /customer/nutrition-plans/{id}
```
**Auth:** Sanctum (role: customer, scoped to own)  
**Response:** Full plan with daily meals, calories, progress tracking.

#### 2.3.9 Cart (already exists)

| Method | Endpoint | Notes |
|--------|----------|-------|
| GET | `/customer/cart` | Get cart |
| POST | `/customer/cart/add` | Add item |
| DELETE | `/customer/cart/{id}` | Remove item |
| POST | `/customer/orders/checkout` | Place order |

#### 2.3.10 Health Profile (already exists)

| Method | Endpoint |
|--------|----------|
| GET | `/user/health-profile` |
| PUT | `/user/health-profile` |

#### 2.3.11 AI Assistant (already exists)

| Method | Endpoint |
|--------|----------|
| POST | `/customer/ai/ask` |
| GET | `/customer/ai/suggestions` |
| POST | `/customer/ai/identify` |
| GET | `/customer/ai/diet-plan` |
| GET | `/customer/ai/chat/history` |

---

## 3. Vendor Role

**Dashboard path:** `/dashboard?role=vendor`  
**DB role value:** `vendor`

The Vendor sells products on the platform. Their dashboard focuses on product management, order fulfillment, inventory, and earnings.

### 3.1 Pages / Sections

| # | Section | Page | Description |
|---|---------|------|-------------|
| 1 | Main | Overview | Summary of products, orders, revenue, ratings |
| 2 | Main | My Products | List/manage own products (CRUD) |
| 3 | Main | Add Product | Create new product form |
| 4 | Main | Orders Received | Orders containing vendor's products |
| 5 | Business | Inventory | Stock levels, low-stock alerts |
| 6 | Business | Earnings | Revenue, payouts, transaction history |
| 7 | Business | Store Settings | Store name, description, logo, policies |
| 8 | Feedback | Reviews | Customer reviews on vendor's products |

### 3.2 Permissions (What Vendor Can Do)

- CRUD own products only
- View orders containing own products
- Update order status for own products (processing → shipped)
- View inventory levels
- Set low-stock thresholds
- View earnings and payouts
- Manage store profile/settings
- View and reply to product reviews
- Upload product images

### 3.3 Required APIs

#### 3.3.1 Dashboard Overview

```
GET /vendor/dashboard/overview
```
**Auth:** Sanctum (role: vendor)  
**Response:**
```json
{
  "success": true,
  "data": {
    "total_products": 48,
    "active_products": 42,
    "orders_received_today": 5,
    "orders_received_total": 156,
    "total_revenue": 8432.00,
    "revenue_this_month": 2340.00,
    "average_rating": 4.7,
    "low_stock_products": [
      { "id": 1, "name": "Fresh Strawberries", "stock_quantity": 12, "min_stock_threshold": 10 }
    ],
    "recent_orders": [
      {
        "id": 1,
        "order_number": "ORD-4821",
        "customer_name": "Sarah Johnson",
        "total_amount": 89.50,
        "status": "pending",
        "items_count": 3,
        "created_at": "30 min ago"
      }
    ],
    "earnings_trend": {
      "last_7_days": [120, 340, 280, 410, 390, 510, 450],
      "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
    }
  }
}
```

#### 3.3.2 Vendor Products

```
GET /vendor/products
```
**Query:** `page`, `per_page`, `category_id`, `is_active`, `search`  
**Auth:** Sanctum (role: vendor, auto-filtered to own)

```
POST /vendor/products
```
**Body:** `{ "name", "description", "price", "compare_at_price", "category_id", "stock_quantity", "is_active", "nutrition_data", "sku", "weight_kg" }`  
**Auth:** Sanctum (role: vendor)  
**Note:** `vendor_id` auto-set to current user

```
GET /vendor/products/{id}
PUT /vendor/products/{id}
DELETE /vendor/products/{id}
```
**Auth:** Sanctum (role: vendor, scoped to own)

```
POST /vendor/products/upload-image
```
**Body:** `multipart/form-data` with `image` field

```
GET /vendor/products/stats
```
**Response:** `{ "total_products", "active_products", "inactive_products", "low_stock_products", "out_of_stock_products", "total_revenue" }`

#### 3.3.3 Vendor Orders

```
GET /vendor/orders
```
**Query:** `page`, `per_page`, `status`  
**Auth:** Sanctum (role: vendor)  
**Note:** Returns only orders containing at least one of vendor's products. Include `items` with vendor product details.

```
GET /vendor/orders/{id}
```
**Auth:** Sanctum (role: vendor)  
**Note:** Only if order contains vendor's products.

```
PUT /vendor/orders/{id}/items/{item_id}/status
```
**Body:** `{ "status": "processing" | "shipped" }`  
**Auth:** Sanctum (role: vendor)  
**Note:** Vendor can update status only for their own line items within an order. Line item statuses feed into the overall order status.

#### 3.3.4 Inventory

```
GET /vendor/inventory
```
**Auth:** Sanctum (role: vendor)  
**Response:** List of own products with stock info, low-stock flagged.

```
PUT /vendor/inventory/{product_id}
```
**Body:** `{ "stock_quantity": int, "min_stock_threshold": int }`  
**Auth:** Sanctum (role: vendor, scoped to own)

#### 3.3.5 Earnings

```
GET /vendor/earnings
```
**Query:** `period` (7|30|90), `from`, `to`  
**Auth:** Sanctum (role: vendor)  
**Response:**
```json
{
  "total_revenue": 8432.00,
  "total_paid": 6000.00,
  "total_pending": 2432.00,
  "current_balance": 2432.00,
  "transactions": [
    { "id": 1, "amount": 450.00, "type": "earned", "order_number": "ORD-4821", "date": "2026-05-28" },
    { "id": 2, "amount": 1200.00, "type": "payout", "date": "2026-05-25" }
  ]
}
```

#### 3.3.6 Store Settings

```
GET /vendor/store
PUT /vendor/store
```
**Body (PUT):** `{ "store_name", "store_description", "store_logo_url", "store_banner_url", "return_policy", "shipping_policy", "contact_email", "contact_phone" }`  
**Auth:** Sanctum (role: vendor)

#### 3.3.7 Vendor Reviews

```
GET /vendor/reviews
```
**Query:** `page`, `per_page`, `rating`  
**Auth:** Sanctum (role: vendor)  
**Response:** Paginated reviews for vendor's products.

```
POST /vendor/reviews/{id}/reply
```
**Body:** `{ "reply": "string" }`  
**Auth:** Sanctum (role: vendor)

---

## 4. Nutritionist Role

**Dashboard path:** `/dashboard?role=nutritionist`  
**DB role value:** `nutritionist`

The Nutritionist is a health expert who creates meal plans, consults with clients, and manages dietary programs.

### 4.1 Pages / Sections

| # | Section | Page | Description |
|---|---------|------|-------------|
| 1 | Main | Overview | Summary of clients, meal plans, appointments |
| 2 | Main | My Clients | List of assigned clients with their health profiles |
| 3 | Main | Meal Plans | Create/manage meal plans for clients |
| 4 | Main | Diet Charts | Create diet charts and nutrition guidance |
| 5 | Schedule | Consultations | Past consultation records |
| 6 | Schedule | Appointments | Upcoming and past appointments with clients |
| 7 | Resources | Articles | Write/publish nutrition articles |
| 8 | Resources | Profile | Nutritionist's own professional profile |

### 4.2 Permissions (What Nutritionist Can Do)

- View assigned clients and their health profiles
- Create/manage meal plans per client
- Create diet charts
- Set appointment slots and manage bookings
- Record consultation notes
- Write and publish nutrition articles
- View own rating/reviews from clients
- Manage own professional profile

### 4.3 Required APIs

#### 4.3.1 Dashboard Overview

```
GET /nutritionist/dashboard/overview
```
**Auth:** Sanctum (role: nutritionist)  
**Response:**
```json
{
  "success": true,
  "data": {
    "active_clients": 18,
    "meal_plans_created": 24,
    "total_appointments_today": 4,
    "upcoming_appointments": [
      {
        "id": 1,
        "client_name": "Sarah Johnson",
        "type": "Consultation",
        "scheduled_at": "2026-05-30 09:00",
        "status": "confirmed"
      }
    ],
    "client_growth": "3 new this month",
    "average_rating": 4.9,
    "meal_plans_summary": [
      { "name": "Weight Loss Plan", "client_count": 8, "meal_count": 21 }
    ]
  }
}
```

#### 4.3.2 Clients

```
GET /nutritionist/clients
```
**Query:** `page`, `per_page`, `search`  
**Auth:** Sanctum (role: nutritionist)  
**Response:** Paginated list of assigned clients.

```
GET /nutritionist/clients/{id}
```
**Auth:** Sanctum (role: nutritionist)  
**Response:** Client details with health profile, assigned plans, consultation history.

```
POST /nutritionist/clients
```
**Body:** `{ "client_user_id": int }`  
**Auth:** Sanctum (role: nutritionist)  
**Note:** Assign a user as client to this nutritionist. Creates record in `nutritionist_client` pivot table.

#### 4.3.3 Meal Plans

```
GET /nutritionist/meal-plans
```
**Query:** `client_id` (optional filter), `page`, `per_page`  
**Auth:** Sanctum (role: nutritionist)

```
POST /nutritionist/meal-plans
```
**Body:**
```json
{
  "client_id": 1,
  "name": "Weight Loss Plan",
  "description": "A balanced 1800-calorie plan",
  "duration_days": 30,
  "daily_calories": 1800,
  "meals": [
    { "day": 1, "meal_type": "breakfast", "name": "Oatmeal with berries", "calories": 350, "notes": "" },
    { "day": 1, "meal_type": "lunch", "name": "Grilled chicken salad", "calories": 450, "notes": "" }
  ]
}
```
**Auth:** Sanctum (role: nutritionist)

```
GET /nutritionist/meal-plans/{id}
PUT /nutritionist/meal-plans/{id}
DELETE /nutritionist/meal-plans/{id}
```

#### 4.3.4 Diet Charts

```
GET /nutritionist/diet-charts
POST /nutritionist/diet-charts
GET /nutritionist/diet-charts/{id}
PUT /nutritionist/diet-charts/{id}
DELETE /nutritionist/diet-charts/{id}
```
**Body (POST/PUT):**
```json
{
  "client_id": 1,
  "title": "Low GI Diet Chart",
  "description": "For diabetic management",
  "days": [
    {
      "day_number": 1,
      "meals": [
        { "time": "07:00", "meal": "Scrambled eggs with spinach", "portion": "2 eggs + 1 cup", "notes": "" }
      ]
    }
  ]
}
```
**Auth:** Sanctum (role: nutritionist)

#### 4.3.5 Appointments

```
GET /nutritionist/appointments
```
**Query:** `status` (confirmed|completed|cancelled), `from`, `to`, `page`, `per_page`  
**Auth:** Sanctum (role: nutritionist)

```
POST /nutritionist/appointments
```
**Body:** `{ "client_id": int, "scheduled_at": "datetime", "type": "consultation|follow-up", "notes": "string" }`  
**Auth:** Sanctum (role: nutritionist)

```
PUT /nutritionist/appointments/{id}/status
```
**Body:** `{ "status": "confirmed" | "completed" | "cancelled" }`  
**Auth:** Sanctum (role: nutritionist)

```
GET /nutritionist/appointments/{id}
```
**Auth:** Sanctum (role: nutritionist)

#### 4.3.6 Consultations (Records)

```
GET /nutritionist/consultations
```
**Query:** `client_id`, `page`, `per_page`  
**Auth:** Sanctum (role: nutritionist)

```
POST /nutritionist/consultations
```
**Body:** `{ "client_id": int, "appointment_id": int, "notes": "string", "recommendations": "string", "follow_up_date": "date" }`  
**Auth:** Sanctum (role: nutritionist)

```
GET /nutritionist/consultations/{id}
PUT /nutritionist/consultations/{id}
```

#### 4.3.7 Articles

```
GET /nutritionist/articles
POST /nutritionist/articles
GET /nutritionist/articles/{id}
PUT /nutritionist/articles/{id}
DELETE /nutritionist/articles/{id}
```
**Body (POST/PUT):** `{ "title": "string", "content": "text", "image_url": "string", "tags": ["nutrition", "health"], "is_published": bool }`  
**Auth:** Sanctum (role: nutritionist)

#### 4.3.8 Nutritionist Profile

```
GET /nutritionist/profile
PUT /nutritionist/profile
```
**Body (PUT):** `{ "bio": "string", "specialization": "string", "qualifications": "string", "experience_years": int, "profile_image": "string", "consultation_fee": float }`  
**Auth:** Sanctum (role: nutritionist)

---

## 5. Super Admin Role

**Dashboard path:** `/dashboard?role=super-admin`  
**DB role value:** `admin`

The Super Admin has full platform oversight — managing users, vendors, nutritionists, products, orders, payments, and system settings.

### 5.1 Pages / Sections

| # | Section | Page | Description |
|---|---------|------|-------------|
| 1 | Main | Overview | Platform KPIs, system health, recent activity |
| 2 | Main | Users | Manage all customer accounts (CRUD) |
| 3 | Main | Vendors | Manage vendor accounts, approve/reject |
| 4 | Main | Nutritionists | Manage nutritionist accounts, approve/reject |
| 5 | Management | Products | Manage all products across all vendors |
| 6 | Management | Orders | View/manage all orders platform-wide |
| 7 | Management | Payments | Payment transactions, refunds, payouts |
| 8 | System | Analytics | Revenue, user growth, platform trends |
| 9 | System | Settings | Platform-wide settings and configuration |
| 10 | System | Audit Log | Track admin actions for security |

### 5.2 Permissions (What Super Admin Can Do)

- Full CRUD on all users (customers, vendors, nutritionists)
- Approve/reject vendor and nutritionist registrations
- CRUD on all products (any vendor)
- View all orders platform-wide
- Update any order status
- Process refunds
- View all payment transactions
- Manage platform settings
- View analytics and trends
- View audit logs
- Manage categories (CRUD)
- Manage discount/coupon codes

### 5.3 Required APIs

#### 5.3.1 Dashboard Overview

```
GET /admin/dashboard/overview
```
**Auth:** Sanctum (role: admin)  
**Response:**
```json
{
  "success": true,
  "data": {
    "total_users": 2847,
    "total_vendors": 86,
    "total_nutritionists": 24,
    "total_orders": 4521,
    "total_revenue": 48290.00,
    "revenue_this_month": 12450.00,
    "orders_this_month": 389,
    "pending_vendors": 3,
    "pending_nutritionists": 1,
    "system_health": {
      "server_status": "operational",
      "active_sessions": 1234,
      "api_response_time_ms": 42,
      "error_rate_percent": 0.02
    },
    "recent_orders": [
      { "id": 1, "order_number": "ORD-4821", "customer_name": "Sarah Johnson", "total": 89.50, "status": "delivered", "created_at": "2 hours ago" }
    ]
  }
}
```

#### 5.3.2 Trends & Analytics

```
GET /admin/dashboard/trends
```
**Query:** `period` (7|30|90), `from`, `to`  
**Auth:** Sanctum (role: admin)  
**Response:** Revenue trend, order trend, top products, top customers.

```
GET /admin/analytics
```
**Query:** `from`, `to`, `group_by` (day|week|month)  
**Auth:** Sanctum (role: admin)  
**Response:**
```json
{
  "revenue_over_time": [{ "date": "2026-05-01", "revenue": 1250.00, "orders": 42 }],
  "user_growth": [{ "date": "2026-05-01", "new_users": 15 }],
  "top_selling_products": [{ "id": 1, "name": "Organic Avocados", "total_sold": 234, "revenue": 702.00 }],
  "orders_by_status": { "pending": 45, "processing": 23, "shipped": 67, "delivered": 432, "cancelled": 12 }
}
```

#### 5.3.3 User Management (Super Admin)

```
GET /admin/users
```
**Query:** `page`, `per_page`, `role` (customer|vendor|nutritionist|admin), `search`, `is_active`  
**Auth:** Sanctum (role: admin)

```
GET /admin/users/{id}
PUT /admin/users/{id}
DELETE /admin/users/{id}
```
**Body (PUT):** `{ "name", "email", "role", "is_active", "notes" }`  
**Auth:** Sanctum (role: admin)

```
PUT /admin/users/{id}/approve
```
**Auth:** Sanctum (role: admin)  
**Note:** Approve vendor or nutritionist account.

```
PUT /admin/users/{id}/suspend
```
**Auth:** Sanctum (role: admin)

#### 5.3.4 Admin Product Management

```
GET /admin/products
POST /admin/products
GET /admin/products/{id}
PUT /admin/products/{id}
DELETE /admin/products/{id}
POST /admin/products/upload-image
```
**Auth:** Sanctum (role: admin)  
**Note:** Same as vendor products API but can access ALL products (not scoped).

#### 5.3.5 Admin Order Management

```
GET /admin/orders
```
**Query:** `page`, `per_page`, `status`, `payment_status`, `vendor_id`, `search`  
**Auth:** Sanctum (role: admin)

```
GET /admin/orders/{id}
PUT /admin/orders/{id}/status
```
**Body:** `{ "status": "pending|processing|shipped|delivered|cancelled", "notes": "string" }`  
**Auth:** Sanctum (role: admin)

```
POST /admin/orders/{id}/refund
```
**Body:** `{ "amount": float, "reason": "string" }`  
**Auth:** Sanctum (role: admin)

#### 5.3.6 Payments

```
GET /admin/payments
```
**Query:** `page`, `per_page`, `status`, `from`, `to`  
**Auth:** Sanctum (role: admin)  
**Response:** All payment transactions including order payments and vendor payouts.

```
GET /admin/payments/{id}
PUT /admin/payments/{id}/status
```

#### 5.3.7 Category Management (already partially exists)

```
GET /admin/categories
POST /admin/categories
GET /admin/categories/{id}
PUT /admin/categories/{id}
DELETE /admin/categories/{id}
```
**Auth:** Sanctum (role: admin)

#### 5.3.8 Settings

```
GET /admin/settings
PUT /admin/settings
```
**Body (PUT):** `{ "site_name", "site_description", "logo_url", "currency", "tax_rate", "shipping_free_threshold", "shipping_fee", "commission_rate", "maintenance_mode", "contact_email" }`  
**Auth:** Sanctum (role: admin)  
**Note:** Store as key-value in a `settings` table or a single settings model.

#### 5.3.9 Audit Log

```
GET /admin/audit-logs
```
**Query:** `page`, `per_page`, `user_id`, `action`, `from`, `to`  
**Auth:** Sanctum (role: admin)  
**Response:**
```json
{
  "data": [
    { "id": 1, "user_id": 1, "user_name": "Admin", "action": "user.created", "details": "Created user #42", "ip_address": "127.0.0.1", "created_at": "2026-05-30T10:00:00Z" }
  ]
}
```

#### 5.3.10 Vendor Management

```
GET /admin/vendors
```
**Query:** `page`, `per_page`, `is_approved`, `search`  
**Auth:** Sanctum (role: admin)  
**Response:** List of all vendor users with store info and stats.

```
GET /admin/vendors/{id}
PUT /admin/vendors/{id}/approve
PUT /admin/vendors/{id}/suspend
GET /admin/vendors/{id}/products
```

#### 5.3.11 Nutritionist Management

```
GET /admin/nutritionists
GET /admin/nutritionists/{id}
PUT /admin/nutritionists/{id}/approve
PUT /admin/nutritionists/{id}/suspend
```
**Auth:** Sanctum (role: admin)

---

## 6. Shared / Cross-Role APIs

These APIs are used by multiple roles or are public.

| Method | Endpoint | Roles | Notes |
|--------|----------|-------|-------|
| GET | `/products` | Public | All roles can browse |
| GET | `/products/{id}` | Public | |
| GET | `/products/categories` | Public | |
| GET | `/products/featured` | Public | Featured products for homepage |
| GET | `/products/search` | Public | AI-powered search |
| POST | `/auth/register` | Public | Registration |
| POST | `/auth/login` | Public | Login |
| GET | `/auth/me` | All authenticated | |
| PUT | `/auth/me` | All authenticated | Update own profile |
| PUT | `/auth/change-password` | All authenticated | |
| GET | `/notifications` | All authenticated | Notification bell |
| PUT | `/notifications/{id}/read` | All authenticated | Mark as read |

---

## 7. Database Schema — New & Modified Tables

### 7.1 New Tables Required

#### nutritionist_clients (pivot)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users (role=nutritionist) |
| client_id | bigint | FK → users (role=customer) |
| assigned_at | datetime | |
| status | string | active, inactive |
| notes | text | Nullable |

#### meal_plans

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users |
| client_id | bigint | FK → users (nullable, null = public/template) |
| name | string | |
| description | text | Nullable |
| duration_days | int | |
| daily_calories | int | Nullable |
| is_template | bool | Default: false |
| timestamps | | |

#### meal_plan_meals

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| meal_plan_id | bigint | FK → meal_plans |
| day | int | Day number |
| meal_type | string | breakfast, lunch, dinner, snack |
| name | string | |
| calories | int | Nullable |
| protein_g | float | Nullable |
| carbs_g | float | Nullable |
| fat_g | float | Nullable |
| notes | text | Nullable |

#### diet_charts

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users |
| client_id | bigint | FK → users |
| title | string | |
| description | text | Nullable |
| timestamps | | |

#### diet_chart_days

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| diet_chart_id | bigint | FK → diet_charts |
| day_number | int | |
| meals | json | Array of {time, meal, portion, notes} |

#### appointments

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users |
| client_id | bigint | FK → users |
| scheduled_at | datetime | |
| type | string | consultation, follow-up |
| status | string | pending, confirmed, completed, cancelled |
| notes | text | Nullable |
| timestamps | | |

#### consultations

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users |
| client_id | bigint | FK → users |
| appointment_id | bigint | FK → appointments (nullable) |
| notes | text | |
| recommendations | text | Nullable |
| follow_up_date | date | Nullable |
| timestamps | | |

#### articles

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| nutritionist_id | bigint | FK → users |
| title | string | |
| slug | string | Unique |
| content | text | |
| image_url | string | Nullable |
| tags | json | Nullable |
| is_published | bool | Default: false |
| published_at | datetime | Nullable |
| timestamps | | |

#### vendor_stores

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| vendor_id | bigint | FK → users (unique) |
| store_name | string | |
| store_description | text | Nullable |
| store_logo_url | string | Nullable |
| store_banner_url | string | Nullable |
| return_policy | text | Nullable |
| shipping_policy | text | Nullable |
| contact_email | string | Nullable |
| contact_phone | string | Nullable |
| is_approved | bool | Default: false |
| approved_at | datetime | Nullable |
| timestamps | | |

#### wishlists

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| product_id | bigint | FK → products |
| created_at | datetime | |
| | | Unique: (user_id, product_id) |

#### reviews

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| product_id | bigint | FK → products |
| order_id | bigint | FK → orders (nullable, for verified purchases) |
| rating | float | 0.5 - 5.0 (0.5 increments) |
| text | text | Nullable |
| vendor_reply | text | Nullable |
| vendor_replied_at | datetime | Nullable |
| timestamps | | |

#### addresses

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| label | string | Home, Office, etc. |
| street | string | |
| city | string | |
| state | string | |
| zip | string | |
| is_default | bool | Default: false |
| timestamps | | |

#### payment_methods

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| card_last_four | string | Last 4 digits only |
| card_holder | string | |
| expiry_month | int | |
| expiry_year | int | |
| is_default | bool | Default: false |
| timestamps | | |

#### audit_logs

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| action | string | e.g. user.created, order.refunded |
| details | json | Nullable |
| ip_address | string | Nullable |
| user_agent | string | Nullable |
| created_at | datetime | |

#### notifications

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users |
| type | string | order_update, approval, system |
| title | string | |
| body | text | |
| data | json | Nullable |
| is_read | bool | Default: false |
| created_at | datetime | |

#### settings

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| key | string | Unique |
| value | text | |
| group | string | general, payment, shipping, etc. |

#### earnings

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| vendor_id | bigint | FK → users |
| order_id | bigint | FK → orders |
| amount | decimal(10,2) | |
| commission_amount | decimal(10,2) | Platform commission |
| net_amount | decimal(10,2) | amount - commission |
| status | string | pending, paid |
| paid_at | datetime | Nullable |
| timestamps | | |

#### payouts

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| vendor_id | bigint | FK → users |
| amount | decimal(10,2) | |
| status | string | pending, processed, failed |
| payment_method | string | bank_transfer, etc. |
| processed_at | datetime | Nullable |
| notes | text | Nullable |
| timestamps | | |

### 7.2 Modified Tables

#### users — add columns

| Column | Type | Notes |
|--------|------|-------|
| avatar | string | Nullable, profile image URL |
| phone | string | Nullable |
| is_active | bool | Default: true |
| approved_at | datetime | Nullable (for vendor/nutritionist approval) |
| notes | text | Nullable (admin notes) |

#### orders — add nullable columns

| Column | Type | Notes |
|--------|------|-------|
| tracking_number | string | Nullable |
| estimated_delivery | datetime | Nullable |
| delivered_at | datetime | Nullable |

#### products — ensure nullable

| Column | Type | Notes |
|--------|------|-------|
| badge | string | Nullable, e.g. "Hot", "Sale", "New", "Organic" |
| short_description | string | Nullable |
| unit | string | Nullable, e.g. "1 kg", "500 ml" |
| is_featured | bool | Default: false |
| min_stock_threshold | int | Default: 5 |

---

> **Handoff Note:** The Laravel backend should create the above tables via migrations, implement the corresponding API controllers with FormRequest validation, and ensure Sanctum middleware enforces role-based access via middleware or gates. All list endpoints should support pagination and the standard `{ success, data, message }` response envelope.
