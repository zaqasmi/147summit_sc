<?php

namespace Tests\Unit;

use App\Support\PublicStorageUrl;
use Tests\TestCase;

class PublicStorageUrlTest extends TestCase
{
    public function test_it_normalizes_public_upload_paths(): void
    {
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('cms/team/member.jpg'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('public/cms/team/member.jpg'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('storage/cms/team/member.jpg'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('/cms/team/member.jpg'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('["cms/team/member.jpg"]'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('/Users/site/storage/app/public/cms/team/member.jpg'));
        $this->assertSame('http://localhost/uploads/cms/team/member.jpg', PublicStorageUrl::make('/Users/site/public/storage/cms/team/member.jpg'));
        $this->assertSame('https://example.com/member.jpg', PublicStorageUrl::make('https://example.com/member.jpg'));
        $this->assertSame('/website-assets/member.jpg', PublicStorageUrl::make('/website-assets/member.jpg'));
        $this->assertNull(PublicStorageUrl::make(null));
    }
}
