<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference
 * @property int $passenger_id
 * @property int $trip_id
 * @property int $seats
 * @property int $unit_price
 * @property int $total_amount
 * @property PaymentMethod $payment_method
 * @property BookingStatus $status
 * @property string|null $idempotency_key
 * @property Carbon|null $boarded_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'reference',
    'passenger_id',
    'trip_id',
    'seats',
    'unit_price',
    'total_amount',
    'payment_method',
    'status',
    'idempotency_key',
    'boarded_at',
    'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'unit_price' => 'integer',
            'total_amount' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => BookingStatus::class,
            'boarded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique, human-usable booking reference.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'AD-'.strtoupper(Str::random(8));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Determine whether the booking still holds seats on the trip.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Get the passenger who owns the booking.
     *
     * @return BelongsTo<User, $this>
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    /**
     * Get the trip the booking belongs to.
     *
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the payment settling the booking, if any.
     *
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
