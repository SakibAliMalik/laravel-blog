# Laravel Blog Package

A reusable blog module for Laravel applications. Provides a complete blog API with posts, categories, tags, and media management out of the box.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Laravel Sanctum (for admin route authentication)

## Installation

```bash
composer require sakibalimalik/laravel-blog
```

## Configuration

### 1. Publish the config file

```bash
php artisan vendor:publish --tag=blog-config
```

This creates `config/blog.php` in your project:

```php
return [
    'user_model'        => \App\Models\User::class, // Your User model
    'admin_middleware'  => ['auth:sanctum'],         // Middleware for admin routes
    'storage_disk'      => 'public',                 // Disk for media uploads
];
```

Update `user_model` if your User model lives in a different namespace.

### 2. Run migrations

> **Skip this step** if your project already has blog tables.

```bash
php artisan vendor:publish --tag=blog-migrations
php artisan migrate
```

### 3. Register the routes

Add this line to your `routes/api.php` (or whichever route file you use):

```php
require base_path('vendor/sakibalimalik/laravel-blog/routes/blog.php');
```

### 4. Create storage symlink (if not already done)

```bash
php artisan storage:link
```

## Routes

All routes are prefixed with whatever prefix your route file uses (e.g. `api/v1`).

### Public Routes (no authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/blog/listing` | List published posts |
| GET | `/blog/show/{slug}` | Get single post by slug |
| GET | `/blog/categories` | List all categories |
| GET | `/blog/tags` | List tags with post counts |
| GET | `/blog/recent` | Get recent posts |
| POST | `/blog/views` | Track a post view |

### Admin Routes (requires auth middleware)

#### Posts
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/blog/posts` | List all posts |
| POST | `/admin/blog/posts` | Create a post |
| GET | `/admin/blog/posts/{id}` | Get a post |
| PUT | `/admin/blog/posts/{id}` | Update a post |
| DELETE | `/admin/blog/posts/{id}` | Delete a post |
| POST | `/admin/blog/posts/{id}/duplicate` | Duplicate a post |
| POST | `/admin/blog/posts/bulk-action` | Bulk publish/unpublish/archive/delete |
| GET | `/admin/blog/posts/statistics` | Get post statistics |

#### Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/blog/categories` | List categories with tree |
| POST | `/admin/blog/categories` | Create a category |
| GET | `/admin/blog/categories/{id}` | Get a category |
| PUT | `/admin/blog/categories/{id}` | Update a category |
| DELETE | `/admin/blog/categories/{id}` | Delete a category |

#### Tags
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/blog/tags` | List all tags |
| POST | `/admin/blog/tags` | Create a tag |
| GET | `/admin/blog/tags/{id}` | Get a tag |
| PUT | `/admin/blog/tags/{id}` | Update a tag |
| DELETE | `/admin/blog/tags/{id}` | Delete a tag |
| GET | `/admin/blog/tags/popular` | Get popular tags |

#### Media
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/blog/media` | List media files |
| POST | `/admin/blog/media/upload` | Upload a file |
| PUT | `/admin/blog/media/{id}` | Update media metadata |
| DELETE | `/admin/blog/media/{id}` | Delete media file |

## Filtering & Pagination

All listing endpoints accept a `query` object and `pagination` object:

```json
{
    "query": {
        "search": "laravel",
        "status": "published",
        "category": 1,
        "date_from": "2025-01-01",
        "date_to": "2025-12-31"
    },
    "pagination": {
        "page": 1,
        "per_page": 15
    }
}
```

### Post filters
- `search` — searches title, content, excerpt
- `status` — `draft`, `published`, `scheduled`, `archived`
- `category` — category ID or slug
- `author` — author ID or name
- `tag` — tag ID or slug
- `date_from` / `date_to` — filter by created date
- `published_from` / `published_to` — filter by published date
- `only_trashed` — `true` to show soft-deleted posts

### Category filters
- `search`, `parent_id`, `root_only`, `has_posts`

### Tag filters
- `search`, `color`, `min_posts`

### Media filters
- `search`, `file_type`, `post_id`, `uploader`

## Post Status Values

| Value | Description |
|-------|-------------|
| `draft` | Not visible publicly |
| `published` | Live and visible |
| `scheduled` | Will go live at `published_at` |
| `archived` | Hidden from public |

## Namespace Reference

All classes live under `SakibAliMalik\Blog\`:

```
SakibAliMalik\Blog\Models\Post
SakibAliMalik\Blog\Models\Category
SakibAliMalik\Blog\Models\Tag
SakibAliMalik\Blog\Models\Media
SakibAliMalik\Blog\Models\PostComment
SakibAliMalik\Blog\Models\PostRevision
SakibAliMalik\Blog\Models\PostView
SakibAliMalik\Blog\Enums\PostStatusEnum
SakibAliMalik\Blog\Enums\PostCommentStatusEnum
SakibAliMalik\Blog\Traits\ApiResponseTrait
SakibAliMalik\Blog\Traits\PaginationTrait
SakibAliMalik\Blog\Traits\FileUploadTrait
SakibAliMalik\Blog\Traits\CrudTrait
```

## Updating

To pull the latest changes into any project:

```bash
composer update sakibalimalik/laravel-blog
```

## License

MIT
