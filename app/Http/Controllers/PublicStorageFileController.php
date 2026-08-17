<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageFileController extends Controller
{
    public function __invoke(string $path): StreamedResponse|Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if($path === '' || str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
