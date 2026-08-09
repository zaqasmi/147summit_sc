<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'starts_at',
        'ends_at',
        'registration_closes_at',
        'registration_fee',
        'max_players',
        'rules',
        'match_format',
        'status',
        'draw_generated_at',
        'prize_notes',
        'is_featured',
        'is_published',
        'created_by',
    ];

    protected $appends = [
        'registered_players_count',
        'paid_players_count',
        'pending_payments_count',
        'registration_fee_collected',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'registration_closes_at' => 'datetime',
            'registration_fee' => 'decimal:2',
            'draw_generated_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Tournament $tournament): void {
            if (blank($tournament->slug) && filled($tournament->name)) {
                $baseSlug = Str::slug($tournament->name);
                $slug = $baseSlug;
                $counter = 2;

                while (static::query()
                    ->where('slug', $slug)
                    ->when($tournament->exists, fn ($query) => $query->whereKeyNot($tournament->getKey()))
                    ->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }

                $tournament->slug = $slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class);
    }

    public function getRegisteredPlayersCountAttribute(): int
    {
        return $this->players()->count();
    }

    public function getPaidPlayersCountAttribute(): int
    {
        return $this->players()->where('fee_status', 'paid')->count();
    }

    public function getPendingPaymentsCountAttribute(): int
    {
        return $this->players()->where('fee_status', '!=', 'paid')->count();
    }

    public function getRegistrationFeeCollectedAttribute(): float
    {
        return (float) $this->players()->where('fee_status', 'paid')->sum('registration_fee');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function typeOptions(): array
    {
        return [
            'knockout' => 'Knockout',
            'league' => 'League',
            'round_robin' => 'Round robin',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
        ];
    }

    public static function matchFormatOptions(): array
    {
        return [
            'best_of_3' => 'Best of 3',
            'best_of_5' => 'Best of 5',
            'best_of_7' => 'Best of 7',
            'best_of_9' => 'Best of 9',
        ];
    }
}
