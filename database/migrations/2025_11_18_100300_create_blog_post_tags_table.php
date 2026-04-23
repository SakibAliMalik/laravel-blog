<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'post_tags', function (Blueprint $table) use ($prefix) {
            $table->foreignId('post_id')->constrained($prefix . 'posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained($prefix . 'tags')->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
            $table->timestamps();

            $table->index('post_id');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'post_tags');
    }
};
