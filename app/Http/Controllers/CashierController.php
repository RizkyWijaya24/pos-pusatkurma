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

    /**
     * Impersonate a cashier.
     */
    public function impersonate(User $user)
    {
        // Security check: Only admins can trigger impersonation
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menggunakan fitur ini.');
        }

        // Target check: Cannot impersonate oneself or another admin/owner
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak bisa masuk sebagai diri sendiri.');
        }
        
        if ($user->role !== 'kasir') {
            return redirect()->back()->with('error', 'Hanya kasir yang dapat diakses.');
        }

        // Save original admin ID in session
        session(['impersonate_original_user_id' => auth()->id()]);

        // Login as cashier
        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Berhasil masuk sebagai ' . $user->name);
    }

    /**
     * Leave impersonation and switch back to admin.
     */
    public function leaveImpersonate()
    {
        $originalId = session()->pull('impersonate_original_user_id');

        if (!$originalId) {
            return redirect()->route('dashboard')->with('error', 'Tidak ada sesi impersonasi.');
        }

        $admin = User::find($originalId);

        if (!$admin) {
            return redirect()->route('dashboard')->with('error', 'Admin tidak ditemukan.');
        }

        // Login back as admin
        auth()->login($admin);

        return redirect()->route('admin.dashboard')->with('success', 'Kembali ke akun Admin.');
    }
}
