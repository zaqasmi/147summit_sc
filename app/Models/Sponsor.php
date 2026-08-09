<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'logo_path',
        'description',
        'website_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'government_department' => 'Government department',
            'sports_association' => 'Sports association',
            'national_body' => 'National body',
            'international_federation' => 'International federation',
            'corporate_sponsor' => 'Corporate sponsor',
            'equipment_vendor' => 'Equipment vendor',
            'business_partner' => 'Business partner',
        ];
    }
}
