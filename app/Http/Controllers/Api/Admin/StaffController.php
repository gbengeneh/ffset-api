<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_CASHIER])
            ->orderBy('name')
            ->paginate(20);

        return response()->json($staff);
    }

    public function store(StoreStaffRequest $request)
    {
        $staff = User::create($request->validated());

        // Staff accounts are provisioned by an admin, never self-registered,
        // so there's nothing to verify — mark it verified immediately rather
        // than routing them through the player email-verification flow.
        $staff->forceFill(['email_verified_at' => now()])->save();

        return response()->json($staff->only(['id', 'name', 'email', 'phone', 'role', 'is_active']), 201);
    }

    public function update(UpdateStaffRequest $request, User $staff)
    {
        $staff->update($request->validated());

        return response()->json($staff->only(['id', 'name', 'email', 'phone', 'role', 'is_active']));
    }

    public function destroy(User $staff)
    {
        $staff->update(['is_active' => false]);

        return response()->json($staff->only(['id', 'name', 'email', 'phone', 'role', 'is_active']));
    }
}
