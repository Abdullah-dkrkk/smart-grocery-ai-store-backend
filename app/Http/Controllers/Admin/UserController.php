<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $perPage = $request->input('per_page', 15);
        $users = $query->latest()->paginate($perPage);

        return $this->paginateResponse($users, 'Users retrieved successfully');
    }

    public function show($id)
    {
        $user = User::with(['orders', 'healthProfile'])->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse($user, 'User details retrieved');
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'role' => ['sometimes', Rule::in(['admin', 'vendor', 'customer'])],
        ]);

        $user->update($validated);

        return $this->successResponse($user, 'User updated successfully');
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if ($user->id === request()->user()->id) {
            return $this->errorResponse('Cannot delete your own account.', 409);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
