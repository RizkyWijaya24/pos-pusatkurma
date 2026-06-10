<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Back-fill any NULL or empty string branch values to 'Pusat Cianjur' to avoid ENUM conversion truncation warnings
        DB::table('users')
            ->whereNull('branch')
            ->orWhere('branch', '')
            ->update(['branch' => 'Pusat Cianjur']);

        // 2. Define safe enum values
        $allowedBranches = [
            'Pusat Cianjur',
            'Cabang Sukabumi',
            'Cabang Bandung',
            'Cabang Jakarta',
            'Cabang Cianjur',
            'Cabang Ciranjang',
            'Cabang Rumah'
        ];

        // Gather any other unique values residing in the database to be 100% safe
        $dbBranches = DB::table('users')
            ->distinct()
            ->whereNotNull('branch')
            ->where('branch', '<>', '')
            ->pluck('branch')
            ->toArray();

        $allBranches = array_unique(array_merge($allowedBranches, $dbBranches));

        // Build ENUM values string
        $escapedBranches = array_map(function ($value) {
            return "'" . addslashes($value) . "'";
        }, $allBranches);

        $enumString = implode(', ', $escapedBranches);

        // 3. Alter the branch column in the users table to ENUM safely
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM({$enumString}) NOT NULL DEFAULT 'Pusat Cianjur'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Revert back to VARCHAR(255)
            DB::statement("ALTER TABLE users MODIFY COLUMN branch VARCHAR(255) NOT NULL DEFAULT 'Pusat Cianjur'");
        }
    }
};
