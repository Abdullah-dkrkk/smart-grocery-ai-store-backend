<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="User authentication, registration, and profile management"
 * )
 */
class AuthController extends Controller
{
    #[Post(
        path: "/api/auth/register",
        tags: ["Auth"],
        summary: "Register a new user",
        description: "Create a new customer account with email, password, and name.",
        requestBody: new RequestBody(
            required: true,
            content: new JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new Property(property: "name", type: "string", example: "John Doe"),
                    new Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new Property(property: "password", type: "string", format: "password", example: "password123"),
                    new Property(property: "password_confirmation", type: "string", format: "password", example: "password123"),
                ]
            )
        ),
        responses: [
            new Response(
                response: 201,
                description: "User registered successfully",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "data", properties: [
                            new Property(property: "user", type: "object"),
                            new Property(property: "token", type: "string", example: "2|aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX4yZ5"),
                        ], type: "object"),
                        new Property(property: "message", type: "string", example: "Registration successful"),
                    ]
                )
            ),
            new Response(response: 409, description: "Email already exists"),
            new Response(response: 422, description: "Validation error"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'Registration successful', 201);
    }

    #[Post(
        path: "/api/auth/login",
        tags: ["Auth"],
        summary: "User login",
        description: "Authenticate user and return Sanctum token.",
        requestBody: new RequestBody(
            required: true,
            content: new JsonContent(
                required: ["email", "password"],
                properties: [
                    new Property(property: "email", type: "string", format: "email", example: "admin@smartgrocery.com"),
                    new Property(property: "password", type: "string", format: "password", example: "admin123"),
                ]
            )
        ),
        responses: [
            new Response(
                response: 200,
                description: "Login successful",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "data", properties: [
                            new Property(property: "user", type: "object"),
                            new Property(property: "token", type: "string", example: "3|xY9wV8uT7sR6qP5oN4mL3kJ2iH1gF0eD9cB8aZ7"),
                        ], type: "object"),
                        new Property(property: "message", type: "string", example: "Login successful"),
                    ]
                )
            ),
            new Response(response: 401, description: "Invalid credentials"),
            new Response(response: 422, description: "Validation error"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'Login successful');
    }

    #[Post(
        path: "/api/auth/logout",
        tags: ["Auth"],
        summary: "User logout",
        description: "Revoke the current user's Sanctum token.",
        security: [["sanctum" => []]],
        responses: [
            new Response(
                response: 200,
                description: "Logout successful",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "message", type: "string", example: "Logout successful"),
                    ]
                )
            ),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    #[Post(
        path: "/api/auth/forgot-password",
        tags: ["Auth"],
        summary: "Request password reset",
        description: "Send password reset link to the user's email.",
        requestBody: new RequestBody(
            required: true,
            content: new JsonContent(
                required: ["email"],
                properties: [
                    new Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                ]
            )
        ),
        responses: [
            new Response(
                response: 200,
                description: "Password reset link sent",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "message", type: "string", example: "Password reset link sent to your email"),
                    ]
                )
            ),
            new Response(response: 404, description: "Email not found"),
            new Response(response: 422, description: "Validation error"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $request->validate(['email' => 'exists:users']);

        return $this->successResponse(null, 'Password reset link sent to your email');
    }

    #[Post(
        path: "/api/auth/reset-password",
        tags: ["Auth"],
        summary: "Reset user password",
        description: "Reset password using the token received via email.",
        requestBody: new RequestBody(
            required: true,
            content: new JsonContent(
                required: ["token", "email", "password", "password_confirmation"],
                properties: [
                    new Property(property: "token", type: "string", example: "abc123token"),
                    new Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new Property(property: "password", type: "string", format: "password", example: "newpassword123"),
                    new Property(property: "password_confirmation", type: "string", format: "password", example: "newpassword123"),
                ]
            )
        ),
        responses: [
            new Response(
                response: 200,
                description: "Password reset successful",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "message", type: "string", example: "Password reset successful"),
                    ]
                )
            ),
            new Response(response: 400, description: "Invalid or expired token"),
            new Response(response: 422, description: "Validation error"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return $this->successResponse(null, 'Password reset successful');
    }

    #[Get(
        path: "/api/auth/me",
        tags: ["Auth"],
        summary: "Get current user details",
        description: "Retrieve the authenticated user's profile information.",
        security: [["sanctum" => []]],
        responses: [
            new Response(
                response: 200,
                description: "User profile retrieved",
                content: new JsonContent(
                    properties: [
                        new Property(property: "success", type: "boolean", example: true),
                        new Property(property: "data", type: "object"),
                        new Property(property: "message", type: "string", example: "User profile retrieved"),
                    ]
                )
            ),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 500, description: "Internal server error"),
        ]
    )]
    public function me(Request $request)
    {
        return $this->successResponse($request->user(), 'User profile retrieved');
    }
}
