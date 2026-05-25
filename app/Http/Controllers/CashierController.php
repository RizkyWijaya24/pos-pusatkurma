<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CashierController extends Controller
{
    /**
     * Store a newly created cashier in storage.
     */
    public function store(Request $request)
    {
        $allowedBranches = \App\Models\User::getBranchEnumValues();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'branch'   => ['required', 'string', \Illuminate\Validation\Rule::in($allowedBranches)],
        ]);

        $cashier = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'kasir',
            'branch' => $validated['branch'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kasir berhasil didaftarkan!',
            'cashier' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'email' => $cashier->email,
                'branch' => $cashier->branch,
                'lastActive' => 'Aktif Sekarang'
            ]
        ]);
    }

    /**
     * Remove the specified cashier from storage.
     */
    public function destroy(User $user)
    {
        if ($user->role !== 'kasir') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya akun kasir yang dapat dihapus.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun kasir berhasil dihapus!'
        ]);
    }
}
