<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $transporter_id
 * @property PaymentMethod $method
 * @property int $amount
 * @property int $commission_amount
 * @property PaymentStatus $status
 * @property string|null $provider
 * @property string|null $provider_reference
 * @property string|null $idempotency_key
 * @property Carbon|null $processed_at
 */
#[Fillable([
    'booking_id',
    'transporter_id',
    'method',
    'amount',
    'commission_amount',
    'status',
    'provider',
    'provider_reference',
    'idempotency_key',
    'processed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'commission_amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the booking the payment settles.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the transporter that receives the payment proceeds.
     *
     * @return BelongsTo<Transporter, $this>
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
