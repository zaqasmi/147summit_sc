<?php

namespace App\Models;

use App\Services\GameBillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'snooker_table_id',
        'game_type',
        'status',
        'started_at',
        'ended_at',
        'checked_out_at',
        'frames_played',
        'frame_fee',
        'hourly_rate',
        'discount_total',
        'created_by',
        'notes',
    ];

    protected $appends = [
        'base_total',
        'add_on_total',
        'grand_total',
        'paid_total',
        'outstanding_total',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'frame_fee' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'discount_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GameSession $session): void {
            if ($session->status !== 'active') {
                return;
            }

            $hasActiveSession = static::query()
                ->where('snooker_table_id', $session->snooker_table_id)
                ->where('status', 'active')
                ->when($session->exists, fn (Builder $query): Builder => $query->whereKeyNot($session->getKey()))
                ->exists();

            if ($hasActiveSession) {
                throw ValidationException::withMessages([
                    'snooker_table_id' => 'This table already has an active game. End the current game before starting another one.',
                ]);
            }
        });

        static::saved(function (GameSession $session): void {
            app(GameBillingService::class)->recalculate($session);
        });
    }

    public function snookerTable(): BelongsTo
    {
        return $this->belongsTo(SnookerTable::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GameParticipant::class);
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(GameAddOn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCheckedOut(Builder $query): Builder
    {
        return $query->where('status', 'checked_out');
    }

    public function isFrameGame(): bool
    {
        return in_array($this->game_type, ['one_to_one', 'doubles'], true);
    }

    public function getDurationMinutesAttribute(): int
    {
        $start = $this->started_at instanceof Carbon ? $this->started_at : now();
        $end = $this->ended_at instanceof Carbon ? $this->ended_at : now();

        return max(1, (int) $start->diffInMinutes($end));
    }

    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }

    public function getBaseTotalAttribute(): float
    {
        return (float) $this->participants()->sum('base_amount');
    }

    public function getAddOnTotalAttribute(): float
    {
        return (float) $this->addOns()->sum('total_amount');
    }

    public function getGrandTotalAttribute(): float
    {
        return (float) $this->participants()->sum('total_due');
    }

    public function getPaidTotalAttribute(): float
    {
        return (float) $this->participants()->sum('amount_paid');
    }

    public function getOutstandingTotalAttribute(): float
    {
        return max(0, $this->grand_total - $this->paid_total);
    }
}
