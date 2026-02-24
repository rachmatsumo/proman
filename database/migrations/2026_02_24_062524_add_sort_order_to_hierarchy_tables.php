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
        Schema::table('sub_programs', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('end_date');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('end_date');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_programs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
