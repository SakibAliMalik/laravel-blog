<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::table($prefix . 'posts', function (Blueprint $table) {
            $table->string('pending_job_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        $prefix = config('blog.table_prefix', '');

        Schema::table($prefix . 'posts', function (Blueprint $table) {
            $table->dropColumn('pending_job_id');
        });
    }
};
