<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Models\Trip;
use App\Notifications\DepartureReminderNotification;
use App\Support\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('trips:send-departure-reminders')]
#[Description('Notify passengers holding an active booking of an upcoming trip departure.')]
class SendDepartureReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leadHours = (int) Settings::get('reminder.lead_hours');
        $window = now()->addHours($leadHours);

        $trips = Trip::query()
            ->where('status', TripStatus::Published)
            ->whereNull('departure_reminded_at')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->whereDate('departure_date', '<=', $window->toDateString())
            ->with(['bookings' => fn ($query) => $query
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->with('passenger'),
            ])
            ->get();

        $reminded = 0;

        foreach ($trips as $trip) {
            if (! $trip->departureAt()->between(now(), $window)) {
                continue;
            }

            foreach ($trip->bookings as $booking) {
                $booking->passenger->notify(new DepartureReminderNotification($trip, $booking));
                $reminded++;
            }

            $trip->update(['departure_reminded_at' => now()]);
        }

        $this->info("Rappels de départ envoyés : {$reminded}");

        return self::SUCCESS;
    }
}
