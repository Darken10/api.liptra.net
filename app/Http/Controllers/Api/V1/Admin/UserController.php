<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

final class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles', 'permissions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->role($role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(UserResource::collection($users)->response()->getData(true));
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'permissions', 'bookings', 'companies');

        return $this->success(new UserResource($user));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_indication' => ['nullable', 'string', 'max:5'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'not_specified'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'pending', 'banned'])],
            'role' => ['nullable', 'string'],
        ]);

        $role = $validated['role'] ?? 'user';
        unset($validated['role']);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::query()->create($validated);
        $user->assignRole($role);
        $user->load('roles', 'permissions');

        return $this->created(new UserResource($user));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_indication' => ['nullable', 'string', 'max:5'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'not_specified'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'pending', 'banned'])],
            'role' => ['nullable', 'string'],
        ]);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        $user->update($validated);
        $user->load('roles', 'permissions');

        return $this->success(new UserResource($user));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->noContent();
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user->syncRoles([$validated['role']]);
        $user->load('roles', 'permissions');

        return $this->success(new UserResource($user), 'Role assigned successfully');
    }
}
