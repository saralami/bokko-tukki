<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ProcessMobileMoneyPayment;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileMoneyWebhookController extends Controller
{
    /**
     * Handle an incoming Mobile Money payment confirmation.
     *
     * The endpoint is authenticated by a shared secret and is idempotent: a repeated
     * webhook for the same provider reference never credits the wallet twice.
     */
    public function handle(Request $request, ProcessMobileMoneyPayment $processPayment): JsonResponse
    {
        abort_unless(
            hash_equals((string) config('allodakar.mobile_money.webhook_secret'), (string) $request->header('X-Webhook-Secret')),
            401,
        );

        $validated = $request->validate([
            'booking_reference' => ['required', 'string'],
            'provider_reference' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'provider' => ['nullable', 'string'],
        ]);

        $booking = Booking::query()->where('reference', $validated['booking_reference'])->firstOrFail();

        if ($booking->payment_method !== PaymentMethod::MobileMoney) {
            return response()->json(['message' => 'La réservation n’est pas réglée par Mobile Money.'], 422);
        }

        if ((int) $validated['amount'] !== $booking->total_amount) {
            return response()->json(['message' => 'Le montant ne correspond pas à la réservation.'], 422);
        }

        $payment = $processPayment(
            $booking,
            $validated['provider_reference'],
            (int) $validated['amount'],
            $validated['provider'] ?? null,
        );

        return response()->json([
            'reference' => $payment->provider_reference,
            'status' => $payment->status->value,
        ]);
    }
}
