<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagementTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role_title',
        'photo_path',
        'bio',
        'phone',
        'email',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return PublicStorageUrl::make($this->photo_path);
    }
}
