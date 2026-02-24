<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_programs', function (Blueprint $table) {
            $table->decimal('bobot', 8, 2)->nullable()->after('description');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->decimal('bobot', 8, 2)->nullable()->after('description');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('bobot', 8, 2)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('sub_programs', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('bobot');
        });
    }
};
