<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $expense = Expense::create([
            'cashier_id' => auth()->id(),
            'branch' => auth()->user()->branch ?? 'Cabang Rumah',
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        // Map additional info for UI rendering
        $formattedExpense = [
            'id' => $expense->id,
            'amount' => (int) $expense->amount,
            'category' => $expense->category,
            'description' => $expense->description,
            'time' => $expense->created_at->format('H:i'),
            'date' => $expense->created_at->format('d/m/Y'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dicatat!',
            'expense' => $formattedExpense
        ]);
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        // STRICT SECURITY: Cashier can only delete their own recorded expenses!
        if ($expense->cashier_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk menghapus pengeluaran ini.'
            ], 403);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus.'
        ]);
    }
}
