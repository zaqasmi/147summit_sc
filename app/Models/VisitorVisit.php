<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_name',
        'path',
        'visitor_hash',
        'user_agent',
        'referrer',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
