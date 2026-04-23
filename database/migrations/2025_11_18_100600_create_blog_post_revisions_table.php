<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'post_revisions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('post_id')->constrained($prefix . 'posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->json('content_json')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('revision_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('post_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'post_revisions');
    }
};
