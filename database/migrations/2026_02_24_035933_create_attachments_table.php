<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            // Polymorphic columns
            $table->morphs('attachable'); // attachable_type + attachable_id
            // Category
            $table->enum('type', ['RAB', 'Evidence', 'Paparan', 'Other'])->default('Other');
            // File metadata
            $table->string('name');                  // label/title shown in UI
            $table->string('original_filename');     // original file name
            $table->string('file_path');             // path in storage
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
