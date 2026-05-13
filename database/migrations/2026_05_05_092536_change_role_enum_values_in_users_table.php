<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to values that will exist in BOTH old and new enum
        // 'admin' stays as is (we'll manually map it after ALTER)
        // 'employer' and 'candidate' -> we need to map these to a temp value first
        
        // Step 1: Temporarily change to a string column to allow any value
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'site_admin'");
        
        // Step 2: Update existing data to new values
        DB::statement("UPDATE users SET role = 'super_admin' WHERE role = 'admin'");
        DB::statement("UPDATE users SET role = 'site_admin' WHERE role IN ('employer', 'candidate')");
        
        // Step 3: Change to new enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'site_admin') NOT NULL DEFAULT 'site_admin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: change to string first, update data, then change to old enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'candidate'");
        
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
        DB::statement("UPDATE users SET role = 'candidate' WHERE role = 'site_admin'");
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employer', 'candidate') NOT NULL DEFAULT 'candidate'");
    }
};
