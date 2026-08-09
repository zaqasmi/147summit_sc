<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'group',
        'type',
        'value',
        'sort_order',
    ];

    public static function typeOptions(): array
    {
        return [
            'text' => 'Text',
            'textarea' => 'Long text',
            'url' => 'URL',
            'email' => 'Email',
            'phone' => 'Phone',
            'image' => 'Image path',
        ];
    }
}
