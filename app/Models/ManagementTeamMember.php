<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        if (blank($this->photo_path)) {
            return null;
        }

        if (Str::startsWith($this->photo_path, ['http://', 'https://', '/'])) {
            return $this->photo_path;
        }

        return asset('storage/'.ltrim($this->photo_path, '/'));
    }
}
