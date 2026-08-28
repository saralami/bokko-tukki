<?php

namespace App\Models;

use App\Enums\TransporterStatus;
use App\Support\Settings;
use Database\Factories\TransporterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property TransporterStatus $status
 */
#[Fillable(['user_id', 'company_name', 'email', 'phone', 'address', 'status'])]
class Transporter extends Model
{
    /** @use HasFactory<TransporterFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransporterStatus::class,
        ];
    }

    /**
     * Get the user that owns the transporter company.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the drivers that belong to the transporter.
     *
     * @return HasMany<Driver, $this>
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get the vehicles that belong to the transporter.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the trips operated by the transporter.
     *
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the transporter's wallet.
     *
     * A transporter conceptually always has a wallet; a zeroed default is
     * returned for read paths before any money has moved (the real row is
     * persisted lazily by walletOrCreate()).
     *
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->withDefault([
            'available_balance' => 0,
            'outstanding_debt' => 0,
        ]);
    }

    /**
     * Get the payments received by the transporter.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the withdrawal requests made by the transporter.
     *
     * @return HasMany<Withdrawal, $this>
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get the transporter's wallet, creating an empty one when it does not exist yet.
     */
    public function walletOrCreate(): Wallet
    {
        return $this->wallet()->firstOrCreate([]);
    }

    /**
     * Determine whether the transporter's outstanding debt exceeds the configured ceiling.
     */
    public function hasExceededDebtCeiling(): bool
    {
        $maximum = (int) Settings::get('debt.maximum');

        return $this->walletOrCreate()->outstanding_debt > $maximum;
    }
}
