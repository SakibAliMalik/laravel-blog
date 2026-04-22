<?php

namespace SakibAliMalik\Blog;

use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/blog.php' => config_path('blog.php'),
        ], 'blog-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'blog-migrations');

        $this->publishes([
            __DIR__ . '/../routes/blog.php' => base_path('routes/blog.php'),
        ], 'blog-routes');
    }
}
