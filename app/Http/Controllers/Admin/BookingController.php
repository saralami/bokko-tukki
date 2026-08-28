<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * List every booking on the platform with filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $bookings = Booking::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('passenger', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->with([
                'passenger:id,name',
                'trip.departureDestination:id,city',
                'trip.arrivalDestination:id,city',
                'payment:id,booking_id,status',
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'passenger' => $booking->passenger->name,
                'route' => $booking->trip->departureDestination->city.' → '.$booking->trip->arrivalDestination->city,
                'date' => $booking->trip->departure_date->toDateString(),
                'seats' => $booking->seats,
                'amount' => $booking->total_amount,
                'payment_method' => $booking->payment_method->value,
                'payment_status' => $booking->payment?->status->value ?? 'unpaid',
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
            ]);

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => BookingStatus::values(),
        ]);
    }

    /**
     * Show a single booking with its financial trace.
     */
    public function show(Booking $booking): Response
    {
        $booking->load([
            'passenger:id,name,email',
            'trip.departureDestination:id,city',
            'trip.arrivalDestination:id,city',
            'trip.transporter:id,company_name',
            'payment',
        ]);

        return Inertia::render('admin/bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'passenger' => $booking->passenger->name,
                'passenger_email' => $booking->passenger->email,
                'route' => $booking->trip->departureDestination->city.' → '.$booking->trip->arrivalDestination->city,
                'transporter' => $booking->trip->transporter?->company_name,
                'date' => $booking->trip->departure_date->toDateString(),
                'time' => substr($booking->trip->departure_time, 0, 5),
                'seats' => $booking->seats,
                'unit_price' => $booking->unit_price,
                'amount' => $booking->total_amount,
                'payment_method' => $booking->payment_method->value,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'payment' => $booking->payment ? [
                    'id' => $booking->payment->id,
                    'amount' => $booking->payment->amount,
                    'commission' => $booking->payment->commission_amount,
                    'status' => $booking->payment->status->value,
                    'status_label' => $booking->payment->status->label(),
                ] : null,
            ],
        ]);
    }
}
