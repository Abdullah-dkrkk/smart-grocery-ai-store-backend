<?php

namespace App\OpenApi;

use OpenApi\Attributes\Info;
use OpenApi\Attributes\Contact;
use OpenApi\Attributes\Server;
use OpenApi\Attributes\SecurityScheme;
use OpenApi\Attributes\Tag;

#[Info(
    title: "Smart Grocery AI Store API",
    version: "1.0.0",
    description: "AI-Assisted Health & Grocery Store API. Browse and purchase health-focused grocery products with AI-powered product guidance, personalized diet suggestions, and smart product search.",
    contact: new Contact(
        email: "admin@smartgrocery.com"
    )
)]
#[Server(
    url: "http://localhost:8000",
    description: "Local Development Server"
)]
#[SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Token",
    description: "Enter your API token ONLY (without 'Bearer ' prefix). Get this token from POST /api/auth/login response."
)]

#[Tag(name: "Auth", description: "User authentication, registration, and profile management")]
#[Tag(name: "Products", description: "Product catalog, categories, nutrition data, and AI-powered search")]
#[Tag(name: "Health Profile", description: "User health profiles, dietary preferences, allergies, and fitness goals")]
#[Tag(name: "AI Assistant", description: "AI chat, product recommendations, image recognition, and personalized diet plans")]
#[Tag(name: "Customer Cart", description: "Shopping cart management for customers")]
#[Tag(name: "Customer Orders", description: "Order placement, history, and tracking for customers")]
#[Tag(name: "Admin Products", description: "Admin product management, CRUD operations, and image uploads")]
#[Tag(name: "Admin Orders", description: "Admin order management and status updates")]
#[Tag(name: "Admin Dashboard", description: "Admin analytics, metrics, and reporting")]
#[Tag(name: "Vendor Products", description: "Vendor product management - vendors can ONLY manage their own products")]

// #[Schema(
//     schema: "User",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "name", type: "string"),
//         new Property(property: "email", type: "string"),
//         new Property(property: "role", type: "string", enum: ["admin", "vendor", "customer"]),
//         new Property(property: "created_at", type: "string", format: "date-time"),
//         new Property(property: "updated_at", type: "string", format: "date-time"),
//     ]
// )]
// #[Schema(
//     schema: "Product",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "name", type: "string"),
//         new Property(property: "description", type: "string"),
//         new Property(property: "price", type: "number", format: "float"),
//         new Property(property: "image_url", type: "string", nullable: true),
//         new Property(property: "category_id", type: "integer"),
//         new Property(property: "vendor_id", type: "integer"),
//         new Property(property: "stock_quantity", type: "integer"),
//         new Property(property: "is_active", type: "boolean"),
//         new Property(property: "nutrition_data", type: "object", nullable: true),
//         new Property(property: "created_at", type: "string", format: "date-time"),
//         new Property(property: "updated_at", type: "string", format: "date-time"),
//     ]
// )]
// #[Schema(
//     schema: "Category",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "name", type: "string"),
//         new Property(property: "slug", type: "string"),
//         new Property(property: "description", type: "string"),
//         new Property(property: "image_url", type: "string", nullable: true),
//         new Property(property: "parent_id", type: "integer", nullable: true),
//     ]
// )]
// #[Schema(
//     schema: "HealthProfile",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "user_id", type: "integer"),
//         new Property(property: "age", type: "integer", nullable: true),
//         new Property(property: "weight", type: "number", format: "float", nullable: true),
//         new Property(property: "height", type: "number", format: "float", nullable: true),
//         new Property(property: "goals", type: "string", nullable: true),
//         new Property(property: "allergies", type: "array", items: new Items(type: "string"), nullable: true),
//         new Property(property: "dietary_type", type: "string", nullable: true),
//         new Property(property: "activity_level", type: "string", nullable: true),
//         new Property(property: "medical_conditions", type: "string", nullable: true),
//         new Property(property: "created_at", type: "string", format: "date-time"),
//         new Property(property: "updated_at", type: "string", format: "date-time"),
//     ]
// )]
// #[Schema(
//     schema: "CartItem",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "user_id", type: "integer"),
//         new Property(property: "product_id", type: "integer"),
//         new Property(property: "quantity", type: "integer"),
//         new Property(property: "product", ref: "#/components/schemas/Product"),
//     ]
// )]
// #[Schema(
//     schema: "Order",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "user_id", type: "integer"),
//         new Property(property: "order_number", type: "string"),
//         new Property(property: "total_amount", type: "number", format: "float"),
//         new Property(property: "status", type: "string", enum: ["pending", "processing", "shipped", "delivered", "cancelled"]),
//         new Property(property: "shipping_address", type: "string"),
//         new Property(property: "payment_method", type: "string"),
//         new Property(property: "payment_status", type: "string", enum: ["pending", "paid", "failed"]),
//         new Property(property: "items", type: "array", items: new Items(ref: "#/components/schemas/OrderItem")),
//         new Property(property: "created_at", type: "string", format: "date-time"),
//         new Property(property: "updated_at", type: "string", format: "date-time"),
//     ]
// )]
// #[Schema(
//     schema: "OrderItem",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "order_id", type: "integer"),
//         new Property(property: "product_id", type: "integer"),
//         new Property(property: "quantity", type: "integer"),
//         new Property(property: "price", type: "number", format: "float"),
//         new Property(property: "product", ref: "#/components/schemas/Product"),
//     ]
// )]
// #[Schema(
//     schema: "ChatMessage",
//     properties: [
//         new Property(property: "id", type: "integer"),
//         new Property(property: "user_id", type: "integer"),
//         new Property(property: "message", type: "string"),
//         new Property(property: "response", type: "string"),
//         new Property(property: "type", type: "string", enum: ["text", "image"]),
//         new Property(property: "created_at", type: "string", format: "date-time"),
//     ]
// )]
// #[Schema(
//     schema: "NutritionData",
//     properties: [
//         new Property(property: "calories", type: "integer", nullable: true),
//         new Property(property: "protein", type: "number", format: "float", nullable: true),
//         new Property(property: "carbs", type: "number", format: "float", nullable: true),
//         new Property(property: "fats", type: "number", format: "float", nullable: true),
//         new Property(property: "sugar", type: "number", format: "float", nullable: true),
//         new Property(property: "fiber", type: "number", format: "float", nullable: true),
//         new Property(property: "allergens", type: "array", items: new Items(type: "string"), nullable: true),
//     ]
// )]
class SwaggerInfo
{
}
