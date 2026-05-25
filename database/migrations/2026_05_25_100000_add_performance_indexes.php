<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production Performance Indexes Migration
 *
 * Adds targeted MySQL indexes on the columns most frequently used in
 * WHERE clauses, date-range filters, and GROUP BY aggregations.
 * All indexes are wrapped in `hasIndex()` checks so re-running is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── transactions table ─────────────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            // Queried on nearly every page (Owner/Admin dashboards, weekly/monthly stats)
            if (!$this->hasIndex('transactions', 'transactions_created_at_index')) {
                $table->index('created_at', 'transactions_created_at_index');
            }
            // Queried for branch-filtered dashboards and exports
            if (!$this->hasIndex('transactions', 'transactions_branch_index')) {
                $table->index('branch', 'transactions_branch_index');
            }
            // Composite: cashier-specific daily history in Kasir dashboard
            if (!$this->hasIndex('transactions', 'transactions_cashier_date_index')) {
                $table->index(['cashier_id', 'created_at'], 'transactions_cashier_date_index');
            }
        });

        // ── users table ────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // Queried every time branches dropdown is built (every dashboard load)
            if (!$this->hasIndex('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
            // Composite: fetching kasir list filtered by branch
            if (!$this->hasIndex('users', 'users_role_branch_index')) {
                $table->index(['role', 'branch'], 'users_role_branch_index');
            }
        });

        // ── expenses table ─────────────────────────────────────────────────
        Schema::table('expenses', function (Blueprint $table) {
            // Date-filtered expense queries in every dashboard
            if (!$this->hasIndex('expenses', 'expenses_created_at_index')) {
                $table->index('created_at', 'expenses_created_at_index');
            }
            // Composite: cashier-specific daily expense history
            if (!$this->hasIndex('expenses', 'expenses_cashier_date_index')) {
                $table->index(['cashier_id', 'created_at'], 'expenses_cashier_date_index');
            }
            // Branch-filtered expense queries
            if (!$this->hasIndex('expenses', 'expenses_branch_index')) {
                $table->index('branch', 'expenses_branch_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndexIfExists('transactions_created_at_index');
            $table->dropIndexIfExists('transactions_branch_index');
            $table->dropIndexIfExists('transactions_cashier_date_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_role_index');
            $table->dropIndexIfExists('users_role_branch_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndexIfExists('expenses_created_at_index');
            $table->dropIndexIfExists('expenses_cashier_date_index');
            $table->dropIndexIfExists('expenses_branch_index');
        });
    }

    /**
     * Check if an index exists on a table (prevents duplicate index errors).
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
