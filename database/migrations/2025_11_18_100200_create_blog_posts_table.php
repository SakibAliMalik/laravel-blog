<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'posts', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->json('content_json')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image', 500)->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained($prefix . 'categories')->nullOnDelete();
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('read_time')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('author_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('published_at');
            $table->index('slug');
            $table->index('created_at');
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'posts');
    }
};
