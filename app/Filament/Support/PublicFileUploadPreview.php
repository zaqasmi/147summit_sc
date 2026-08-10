<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Str;

class PublicFileUploadPreview
{
    public static function currentHost(): Closure
    {
        return static function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
            $uploadedFile = $component->getUploadedFile($file, $storedFileNames);

            if (! $uploadedFile || $component->getDiskName() !== 'public') {
                return $uploadedFile;
            }

            if (! Str::startsWith($file, ['http://', 'https://', '/'])) {
                $uploadedFile['url'] = Str::sanitizeUrl(asset('storage/'.ltrim($file, '/')));
            }

            return $uploadedFile;
        };
    }
}
