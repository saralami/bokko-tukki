<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $transporter_id
 * @property int $available_balance
 * @property int $outstanding_debt
 */
#[Fillable(['transporter_id', 'available_balance', 'outstanding_debt'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_balance' => 'integer',
            'outstanding_debt' => 'integer',
        ];
    }

    /**
     * Append an immutable ledger entry and update the wallet balances accordingly.
     *
     * Must be called inside a database transaction with the wallet row locked.
     */
    public function postEntry(
        LedgerEntryType $type,
        int $amount,
        int $balanceDelta,
        int $debtDelta,
        ?Payment $payment = null,
        ?Withdrawal $withdrawal = null,
        ?string $description = null,
    ): LedgerEntry {
        $this->available_balance += $balanceDelta;
        $this->outstanding_debt += $debtDelta;
        $this->save();

        return $this->ledgerEntries()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_delta' => $balanceDelta,
            'debt_delta' => $debtDelta,
            'balance_after' => $this->available_balance,
            'debt_after' => $this->outstanding_debt,
            'payment_id' => $payment?->id,
            'withdrawal_id' => $withdrawal?->id,
            'description' => $description,
        ]);
    }

    /**
     * Get the transporter that owns the wallet.
     *
     * @return BelongsTo<Transporter, $this>
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    /**
     * Get the immutable ledger entries of the wallet.
     *
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
