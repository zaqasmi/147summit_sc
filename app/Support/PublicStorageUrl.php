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
            return self::make(Arr::first($path, fn (mixed $value): bool => filled($value)));
        }

        if (! is_string($path) && ! $path instanceof Stringable) {
            return null;
        }

        $path = str_replace('\\', '/', trim((string) $path));

        if ($path === '') {
            return null;
        }

        $decodedPath = json_decode($path, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedPath) || is_string($decodedPath))) {
            return self::make($decodedPath);
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        foreach (['storage/app/public/', 'public/storage/'] as $segment) {
            if (Str::contains($path, $segment)) {
                $path = Str::after($path, $segment);

                break;
            }
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

        return $path === '' ? null : '/storage/'.$path;
    }
}
