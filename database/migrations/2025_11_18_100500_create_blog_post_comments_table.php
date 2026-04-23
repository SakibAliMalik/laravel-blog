<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'post_comments', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('post_id')->constrained($prefix . 'posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 100)->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_url', 500)->nullable();
            $table->text('content');
            $table->enum('status', ['pending', 'approved', 'spam', 'trash'])->default('pending');
            $table->foreignId('parent_id')->nullable()->constrained($prefix . 'post_comments')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('post_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('parent_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'post_comments');
    }
};
