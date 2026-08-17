<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageFileTest extends TestCase
{
    public function test_public_storage_uploads_can_be_served_without_a_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/team/member.jpg', 'member-photo');

        $response = $this->get(route('public-storage.show', ['path' => 'cms/team/member.jpg']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame('member-photo', $response->streamedContent());
    }

    public function test_public_storage_route_rejects_traversal_paths(): void
    {
        $this->get('/uploads/%2E%2E/.env')->assertNotFound();
    }
}
