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
        Schema::table('job_listings', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('location')->nullable()->change();
            $table->json('details')->nullable()->change();
            $table->json('job_description')->change();
            $table->json('requirements')->change();
            $table->json('benefits')->nullable()->change();
            $table->json('overview')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('location')->nullable()->change();
            $table->text('details')->nullable()->change();
            $table->longText('job_description')->change();
            $table->longText('requirements')->change();
            $table->longText('benefits')->nullable()->change();
            $table->longText('overview')->nullable()->change();
        });
    }
};
