# Smart Grocery AI Store Backend

Laravel 11 backend for an AI-assisted health & grocery store with role-based access control (Admin, Vendor, Customer), Sanctum authentication, and standardized API responses.

> 🔧 Maintained by [Abdullah-dkrkk](https://github.com/Abdullah-dkrkk)

## Features

- **Role-Based Access Control (RBAC):** Admin, Vendor, and Customer roles with strict middleware isolation.
- **Sanctum Authentication:** Stateless Bearer token authentication.
- **Standardized API Responses:** All endpoints return consistent JSON: `{ success: bool, data: mixed, message: string }`.
- **Health Profile Management:** Users can store dietary preferences, allergies, and health goals.
- **AI Assistant Integration:** Mocked endpoints for AI-powered product recommendations and dietary advice.
- **Swagger Documentation:** PHP 8 Attributes for API documentation (no `@OA` docblocks).

## Setup

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run `php artisan key:generate`
5. Run `php artisan migrate --seed`
6. Run `php artisan storage:link`
7. Start the server: `php artisan serve`

## Default Users (After Seeding)

| Role     | Email                  | Password   |
|----------|------------------------|------------|
| Admin    | admin@example.com      | password   |
| Vendor   | vendor@example.com     | password   |
| Customer | customer@example.com   | password   |

## API Endpoints

| Method | Endpoint                          | Role         | Description                  |
|--------|-----------------------------------|--------------|------------------------------|
| POST   | `/api/auth/register`              | Public       | Register new user            |
| POST   | `/api/auth/login`                 | Public       | Login & get token            |
| GET    | `/api/products`                   | Public       | List all products            |
| GET    | `/api/products/{id}`              | Public       | Get product details          |
| POST   | `/api/auth/forgot-password`       | Public       | Request password reset       |
| POST   | `/api/auth/reset-password`        | Public       | Reset password               |
| GET    | `/api/admin/dashboard/stats`      | Admin        | Dashboard statistics         |
| GET    | `/api/admin/products`             | Admin        | List all products            |
| PUT    | `/api/admin/products/{id}`        | Admin        | Update any product           |
| DELETE | `/api/admin/products/{id}`        | Admin        | Delete any product           |
| GET    | `/api/admin/orders`               | Admin        | List all orders              |
| PUT    | `/api/admin/orders/{id}/status`   | Admin        | Update order status          |
| GET    | `/api/vendor/products`            | Vendor       | List own products            |
| POST   | `/api/vendor/products`            | Vendor       | Create product               |
| PUT    | `/api/vendor/products/{id}`       | Vendor       | Update own product           |
| DELETE | `/api/vendor/products/{id}`       | Vendor       | Delete own product           |
| GET    | `/api/customer/cart`              | Customer     | View cart                    |
| POST   | `/api/customer/cart`              | Customer     | Add to cart                  |
| PUT    | `/api/customer/cart/{id}`         | Customer     | Update cart item quantity    |
| DELETE | `/api/customer/cart/{id}`         | Customer     | Remove from cart             |
| POST   | `/api/customer/orders`            | Customer     | Place order                  |
| GET    | `/api/customer/orders`            | Customer     | View order history           |
| GET    | `/api/customer/orders/{id}`       | Customer     | View order details           |
| GET    | `/api/customer/health-profile`    | Customer     | View health profile          |
| PUT    | `/api/customer/health-profile`    | Customer     | Update health profile        |
| POST   | `/api/ai/recommendations`         | Customer     | Get AI product recommendations|
| POST   | `/api/ai/chat`                    | Customer     | Chat with AI assistant       |
| POST   | `/api/ai/identify-product`        | Customer     | Identify product from image  |
