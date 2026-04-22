<?php

namespace SakibAliMalik\Blog\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    public function fileUploader(string $path, $file): ?string
    {
        if (empty($file)) {
            return null;
        }

        $res = $file->store('public/' . $path);

        return str_replace('public/', '', $res);
    }

    public function fileRemover(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        if (Storage::exists('public/' . $path)) {
            Storage::delete('public/' . $path);
            return true;
        }

        $baseUrl = url('/storage');
        $relativePath = str_replace($baseUrl . '/', '', $path);

        if (!empty($relativePath) && Storage::exists('public/' . $relativePath)) {
            Storage::delete('public/' . $relativePath);
            return true;
        }

        return false;
    }
}
