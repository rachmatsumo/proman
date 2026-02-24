<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('loggable'); // loggable_type + loggable_id
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->string('description');          // human-readable summary
            $table->json('old_data')->nullable();   // values before change
            $table->json('new_data')->nullable();   // values after change
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
