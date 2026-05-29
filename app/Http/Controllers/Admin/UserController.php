<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($users, 'Users retrieved');
    }

    public function show($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        return $this->successResponse($user, 'User retrieved');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:customer,vendor,nutritionist,admin',
            'is_active' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $user->update($validated);

        return $this->successResponse($user, 'User updated');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return $this->errorResponse('Cannot delete admin users', 403);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted');
    }

    public function approve($id)
    {
        $user = User::findOrFail($id);

        if (!in_array($user->role, ['vendor', 'nutritionist'])) {
            return $this->errorResponse('Only vendor and nutritionist accounts can be approved', 400);
        }

        $user->update([
            'is_active' => true,
            'approved_at' => now(),
        ]);

        return $this->successResponse($user, 'User approved');
    }

    public function suspend($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return $this->errorResponse('Cannot suspend admin users', 403);
        }

        $user->update(['is_active' => false]);

        return $this->successResponse($user, 'User suspended');
    }
}
