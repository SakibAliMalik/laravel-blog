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
];
