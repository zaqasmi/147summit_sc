<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerCapital extends Model
{
    use HasFactory;

    public const SOURCE_CAPITAL_LIABILITY_PAYMENT = 'capital_liability_payment';

    protected $fillable = [
        'entry_date',
        'type',
        'amount',
        'description',
        'notes',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'investment' => 'Capital added',
            'capital_reduction' => 'Capital recovery income / adjustment',
        ];
    }

    public static function syncFromCapitalLiabilityPayment(CapitalLiabilityPayment $payment): void
    {
        if ($payment->paid_from !== 'owner' || (float) $payment->amount <= 0) {
            self::deleteForSource(self::SOURCE_CAPITAL_LIABILITY_PAYMENT, $payment->id);

            return;
        }

        $payment->loadMissing('capitalLiability');
        $liability = $payment->capitalLiability;
        $title = $liability?->title ?: 'Liability';

        self::query()->updateOrCreate(
            [
                'source_type' => self::SOURCE_CAPITAL_LIABILITY_PAYMENT,
                'source_id' => $payment->id,
            ],
            [
                'entry_date' => $payment->payment_date?->toDateString() ?? today()->toDateString(),
                'type' => 'investment',
                'amount' => round((float) $payment->amount, 2),
                'description' => 'Owner / other source paid liability: '.$title,
                'notes' => $payment->notes,
            ],
        );
    }

    public static function deleteForSource(string $sourceType, int|string|null $sourceId): void
    {
        if (! $sourceId) {
            return;
        }

        self::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_CAPITAL_LIABILITY_PAYMENT => 'Liability payment',
        ];
    }

    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'capital_reduction'
            ? -1 * (float) $this->amount
            : (float) $this->amount;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeOptions()[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function getSourceLabelAttribute(): string
    {
        if (! $this->source_type || ! $this->source_id) {
            return 'Manual';
        }

        $source = self::sourceOptions()[$this->source_type] ?? ucfirst(str_replace('_', ' ', $this->source_type));

        return $source.' #'.$this->source_id;
    }
}
