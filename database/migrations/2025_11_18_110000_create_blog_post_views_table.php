<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'post_views', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('post_id')->constrained($prefix . 'posts')->cascadeOnDelete();
            $table->string('visitor_id', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['post_id', 'visitor_id']);
            $table->index('visitor_id');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'post_views');
    }
};
