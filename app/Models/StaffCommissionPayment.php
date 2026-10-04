<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/** Read-only projection of existing staff payments and monthly closing payments. */
class StaffCommissionPayment extends Model
{
    protected $table = 'commission_payment_records';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public static function recordsQuery(): Builder
    {
        $ledger = DB::table('staff_transactions as payments')
            ->join('staff', 'staff.id', '=', 'payments.staff_id')
            ->leftJoin('monthly_commissions as balances', function (JoinClause $join): void {
                $join->on('balances.staff_id', '=', 'payments.staff_id')
                    ->whereRaw('SUBSTR(balances.month, 1, 7) = SUBSTR(COALESCE(payments.commission_month, payments.transaction_date), 1, 7)');
            })
            ->whereIn('payments.type', ['advance', 'payout'])
            ->where('payments.amount', '>', 0)
            ->where(fn ($query) => $query->where('staff.commission_rate', '>', 0)->orWhereNotNull('balances.id'))
            ->selectRaw('payments.id, payments.staff_id, SUBSTR(COALESCE(payments.commission_month, payments.transaction_date), 1, 7) as commission_month,
                payments.transaction_date as payment_date, payments.type, payments.paid_from, payments.amount, payments.description,
                balances.commission_amount, balances.carried_forward_from_previous as previous_balance,
                balances.advances_deducted + balances.paid_amount as total_paid, balances.balance_due');

        $closing = DB::table('monthly_commissions as balances')
            ->where('balances.paid_amount', '>', 0)
            ->selectRaw("-balances.id as id, balances.staff_id, SUBSTR(balances.month, 1, 7) as commission_month,
                balances.paid_on as payment_date, 'closing_payment' as type, balances.paid_from, balances.paid_amount as amount,
                'Payment recorded at monthly closing' as description, balances.commission_amount,
                balances.carried_forward_from_previous as previous_balance,
                balances.advances_deducted + balances.paid_amount as total_paid, balances.balance_due");

        return static::query()->fromSub($ledger->unionAll($closing), 'commission_payment_records')->with('staff');
    }
}
