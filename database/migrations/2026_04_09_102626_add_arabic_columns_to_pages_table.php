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
        Schema::table('pages', function (Blueprint $table) {
            $table->json('content_ar')->nullable()->after('content');

            $table->string('title_ar')->nullable()->after('title');

            $table->string('meta_title_ar')->nullable()->after('meta_title');
            $table->string('meta_description_ar')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('content_ar');
            $table->dropColumn('title_ar');
            $table->dropColumn('meta_title_ar');
            $table->dropColumn('meta_description_ar');
        });
    }
};
