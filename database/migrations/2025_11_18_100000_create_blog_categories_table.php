<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::create($prefix . 'categories', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained($prefix . 'categories')->cascadeOnDelete();
            $table->unsignedInteger('order_position')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('icon', 500)->nullable();
            $table->string('color', 50)->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('slug');
            $table->index('order_position');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');
        Schema::dropIfExists($prefix . 'categories');
    }
};
