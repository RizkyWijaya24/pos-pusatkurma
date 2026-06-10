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
        // 1. Define allowed branches list including the new 'Cabang Online'
        $allowedBranches = [
            'Pusat Cianjur',
            'Cabang Sukabumi',
            'Cabang Bandung',
            'Cabang Jakarta',
            'Cabang Cianjur',
            'Cabang Ciranjang',
            'Cabang Rumah',
            'Cabang Online'
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

        // Alter the branch column in the users table to include 'Cabang Online'
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM({$enumString}) NOT NULL DEFAULT 'Pusat Cianjur'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define original allowed branches without 'Cabang Online'
        $allowedBranches = [
            'Pusat Cianjur',
            'Cabang Sukabumi',
            'Cabang Bandung',
            'Cabang Jakarta',
            'Cabang Cianjur',
            'Cabang Ciranjang',
            'Cabang Rumah'
        ];

        // Gather existing branches from DB
        $dbBranches = DB::table('users')
            ->distinct()
            ->whereNotNull('branch')
            ->where('branch', '<>', '')
            ->pluck('branch')
            ->toArray();

        // Filter out 'Cabang Online' from users if any exist before modifying enum (back-fill to 'Pusat Cianjur')
        DB::table('users')
            ->where('branch', 'Cabang Online')
            ->update(['branch' => 'Pusat Cianjur']);

        $allBranches = array_diff(array_unique(array_merge($allowedBranches, $dbBranches)), ['Cabang Online']);

        $escapedBranches = array_map(function ($value) {
            return "'" . addslashes($value) . "'";
        }, $allBranches);

        $enumString = implode(', ', $escapedBranches);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN branch ENUM({$enumString}) NOT NULL DEFAULT 'Pusat Cianjur'");
        }
    }
};
