<?php

namespace App\Models;

use App\Enums\TripStatus;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @property int $id
 * @property int $transporter_id
 * @property int|null $vehicle_id
 * @property int|null $driver_id
 * @property int $departure_destination_id
 * @property int $arrival_destination_id
 * @property Carbon $departure_date
 * @property string $departure_time
 * @property int $price_per_seat
 * @property int $capacity
 * @property int $available_seats
 * @property TripStatus $status
 */
#[Fillable([
    'transporter_id',
    'vehicle_id',
    'driver_id',
    'departure_destination_id',
    'arrival_destination_id',
    'departure_date',
    'departure_time',
    'price_per_seat',
    'capacity',
    'available_seats',
    'status',
    'departure_reminded_at',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'departure_reminded_at' => 'datetime',
            'price_per_seat' => 'integer',
            'capacity' => 'integer',
            'available_seats' => 'integer',
            'status' => TripStatus::class,
        ];
    }

    /**
     * Reserve seats on the trip while guaranteeing availability never goes negative.
     *
     * @throws RuntimeException when the requested seats exceed availability
     */
    public function reserveSeats(int $count): void
    {
        if ($count < 1) {
            throw new RuntimeException('The number of seats to reserve must be positive.');
        }

        if ($count > $this->available_seats) {
            throw new RuntimeException('Not enough available seats on this trip.');
        }

        $this->decrement('available_seats', $count);
    }

    /**
     * Release previously reserved seats without exceeding the trip capacity.
     */
    public function releaseSeats(int $count): void
    {
        $this->update([
            'available_seats' => min($this->capacity, $this->available_seats + max(0, $count)),
        ]);
    }

    /**
     * Get the transporter that operates the trip.
     *
     * @return BelongsTo<Transporter, $this>
     */
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    /**
     * Get the vehicle assigned to the trip.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver assigned to the trip.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the departure destination of the trip.
     *
     * @return BelongsTo<Destination, $this>
     */
    public function departureDestination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'departure_destination_id');
    }

    /**
     * Get the arrival destination of the trip.
     *
     * @return BelongsTo<Destination, $this>
     */
    public function arrivalDestination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'arrival_destination_id');
    }

    /**
     * Get the bookings made on the trip.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the incidents reported on the trip.
     *
     * @return HasMany<TripIncident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(TripIncident::class);
    }

    /**
     * Build the full departure moment from the date and time columns.
     */
    public function departureAt(): Carbon
    {
        return Carbon::parse($this->departure_date->toDateString().' '.$this->departure_time);
    }
}
