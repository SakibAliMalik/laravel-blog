<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'media', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('file_type', 50)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained($prefix . 'posts')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('post_id');
            $table->index('uploaded_by');
            $table->index('file_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'media');
    }
};
