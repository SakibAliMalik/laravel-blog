<?php

return [
    /*
     * The Eloquent model used as the author/user in the blog.
     * This should be the fully qualified class name of your User model.
     */
    'user_model' => \App\Models\User::class,

    /*
     * Auth middleware applied to admin blog routes.
     * Change this to match your project's authentication middleware.
     */
    'admin_middleware' => ['auth:sanctum'],

    /*
     * The disk used to store media files.
     */
    'storage_disk' => 'public',

    /*
     * Prefix applied to all blog table names.
     * Default: '' → posts, categories, tags, etc.
     * Set to 'blog_' to use prefixed table names: blog_posts, blog_categories, etc.
     */
    'table_prefix' => '',

    /*
     * The attribute(s) on your User model used as the display name in API responses.
     * String: single field  → 'name'
     * Array:  multiple fields joined with a space → ['first_name', 'last_name']
     */
    'user_name_attribute' => ['first_name', 'last_name'],
];
