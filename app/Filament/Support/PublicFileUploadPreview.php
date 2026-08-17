<?php

namespace App\Filament\Support;

use App\Support\PublicStorageUrl;
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

            $url = PublicStorageUrl::make($file);

            if ($url !== null) {
                $uploadedFile['url'] = Str::sanitizeUrl($url);
            }

            return $uploadedFile;
        };
    }
}
