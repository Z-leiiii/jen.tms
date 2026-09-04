<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class UserController extends Controller
{
    /**
     * GET /users?search=jane — used by the "add member" autocomplete.
     * Excludes the requesting user and caps results; this is a lookup
     * endpoint, not a full user directory.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $users = \App\Models\User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }, fn ($q) => $q->whereRaw('1 = 0')) // no query = no results, avoid dumping the whole user table
            ->where('id', '!=', $request->user()->id)
            ->limit(10)
            ->get();

        return response()->json(['data' => UserResource::collection($users)]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json(['data' => new UserResource($user)]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Your current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->validated('password'))]);

        // Invalidate other sessions/tokens after a password change, keep this one.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated.']);
    }
}
