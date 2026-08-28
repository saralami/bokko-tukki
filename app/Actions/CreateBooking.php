<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\DriverNewBookingNotification;
use App\Notifications\TransporterNewBookingNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBooking
{
    /**
     * Create a booking while preventing overbooking, duplicates and non-idempotent retries.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function __invoke(User $passenger, array $data): Booking
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey !== null) {
            $existing = Booking::query()
                ->where('passenger_id', $passenger->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $booking = DB::transaction(function () use ($passenger, $data, $idempotencyKey): Booking {
            // Lock the trip row so concurrent bookings are serialized and can never oversell.
            $trip = Trip::query()->whereKey($data['trip_id'])->lockForUpdate()->firstOrFail();

            if ($trip->status !== TripStatus::Published) {
                throw ValidationException::withMessages([
                    'trip_id' => 'Ce trajet n’est pas ouvert à la réservation.',
                ]);
            }

            if ($trip->transporter->hasExceededDebtCeiling()) {
                throw ValidationException::withMessages([
                    'trip_id' => 'Réservations suspendues : la dette du transporteur dépasse le seuil autorisé.',
                ]);
            }

            $alreadyBooked = Booking::query()
                ->where('passenger_id', $passenger->id)
                ->where('trip_id', $trip->id)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->exists();

            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'trip_id' => 'Vous avez déjà une réservation active pour ce trajet.',
                ]);
            }

            $seats = (int) $data['seats'];

            if ($seats > $trip->available_seats) {
                throw ValidationException::withMessages([
                    'seats' => 'Places insuffisantes pour ce trajet.',
                ]);
            }

            $trip->decrement('available_seats', $seats);

            return Booking::create([
                'reference' => Booking::generateReference(),
                'passenger_id' => $passenger->id,
                'trip_id' => $trip->id,
                'seats' => $seats,
                'unit_price' => $trip->price_per_seat,
                'total_amount' => $trip->price_per_seat * $seats,
                'payment_method' => $data['payment_method'],
                'status' => BookingStatus::Pending,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        if ($booking->wasRecentlyCreated) {
            $booking->loadMissing(['passenger', 'trip.transporter.user', 'trip.driver']);
            $booking->passenger->notify(new BookingCreatedNotification($booking));
            $booking->trip->transporter->user->notify(new TransporterNewBookingNotification($booking));

            if ($booking->trip->driver !== null) {
                $booking->trip->driver->notify(new DriverNewBookingNotification($booking));
            }
        }

        return $booking;
    }
}
