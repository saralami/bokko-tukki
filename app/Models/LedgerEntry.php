<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * @property int $id
 * @property int $wallet_id
 * @property LedgerEntryType $type
 * @property int $amount
 * @property int $balance_delta
 * @property int $debt_delta
 * @property int $balance_after
 * @property int $debt_after
 * @property int|null $payment_id
 * @property int|null $withdrawal_id
 * @property string|null $description
 */
#[Fillable([
    'type',
    'amount',
    'balance_delta',
    'debt_delta',
    'balance_after',
    'debt_after',
    'payment_id',
    'withdrawal_id',
    'description',
])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    /**
     * Enforce ledger immutability: an entry can never be updated or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Ledger entries are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Ledger entries are immutable and cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'amount' => 'integer',
            'balance_delta' => 'integer',
            'debt_delta' => 'integer',
            'balance_after' => 'integer',
            'debt_after' => 'integer',
        ];
    }

    /**
     * Get the wallet the entry belongs to.
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the payment that produced the entry, if any.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the withdrawal that produced the entry, if any.
     *
     * @return BelongsTo<Withdrawal, $this>
     */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }
}
