<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;

class PublicStorageUrl
{
    public static function make(mixed $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (is_array($path)) {
            $path = Arr::first($path, fn (mixed $value): bool => filled($value));
        }

        if (! is_string($path) && ! $path instanceof Stringable) {
            return null;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $isAbsolutePath = Str::startsWith($path, '/');
        $path = ltrim($path, '/');

        if ($isAbsolutePath && ! Str::startsWith($path, ['cms/', 'public/', 'storage/', 'app/public/'])) {
            return '/'.$path;
        }

        do {
            $originalPath = $path;

            foreach (['public/', 'storage/', 'app/public/'] as $prefix) {
                $path = Str::replaceStart($prefix, '', $path);
            }
        } while ($path !== $originalPath);

        return $path === '' ? null : asset('storage/'.$path);
    }
}
