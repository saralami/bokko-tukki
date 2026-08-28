<?php

namespace App\Http\Controllers\Driver;

use App\Actions\ConfirmBoarding;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ValidateBoardingRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BoardingController extends Controller
{
    /**
     * Display the boarding validation screen (reference entry / QR scan).
     */
    public function create(Request $request): Response
    {
        abort_if($request->user()->driver === null, 403);

        return Inertia::render('driver/Boarding');
    }

    /**
     * Validate a boarding from a booking reference (typed or scanned from a QR).
     *
     * Security: the booking is only boarded when the authenticated driver is the
     * one assigned to its trip (BookingPolicy@confirmBoarding). Confirming boarding
     * triggers the business consequences via the ConfirmBoarding action.
     */
    public function store(ValidateBoardingRequest $request, ConfirmBoarding $confirmBoarding): RedirectResponse
    {
        $data = $request->validated();

        $booking = Booking::query()
            ->where('reference', $data['reference'])
            ->with(['trip.driver', 'trip.transporter', 'passenger:id,name'])
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'reference' => 'Aucune réservation ne correspond à cette référence.',
            ]);
        }

        // The driver may only board passengers of the trips assigned to them.
        if (! Gate::allows('confirmBoarding', $booking)) {
            throw ValidationException::withMessages([
                'reference' => 'Cette réservation ne concerne pas vos trajets.',
            ]);
        }

        if (! empty($data['trip_id']) && $booking->trip_id !== (int) $data['trip_id']) {
            throw ValidationException::withMessages([
                'reference' => 'Cette réservation appartient à un autre trajet.',
            ]);
        }

        try {
            $confirmBoarding($booking);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'reference' => 'Cette réservation ne peut pas être embarquée (déjà embarquée, annulée ou absente).',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Embarquement confirmé — {$booking->passenger->name} ({$booking->reference}).",
        ]);

        return back();
    }
}
