<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission
    |--------------------------------------------------------------------------
    |
    | Allo Dakar earns money exclusively through a commission on bookings.
    | There is no monthly subscription. The rate is a fraction (0.05 = 5%).
    |
    */

    'commission' => [
        'rate' => (float) env('ALLODAKAR_COMMISSION_RATE', 0.05),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transporter debt
    |--------------------------------------------------------------------------
    |
    | When a transporter's outstanding commission debt exceeds this ceiling,
    | new bookings and new trip publications are blocked until it is settled.
    |
    */

    'debt' => [
        'maximum' => (int) env('ALLODAKAR_MAX_COMMISSION_DEBT', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking cancellation rules
    |--------------------------------------------------------------------------
    */

    'cancellation' => [
        'deadline_hours' => (int) env('ALLODAKAR_CANCELLATION_DEADLINE_HOURS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Money
    |--------------------------------------------------------------------------
    |
    | Shared secret used to authenticate incoming payment webhooks.
    |
    */

    'mobile_money' => [
        'webhook_secret' => env('ALLODAKAR_MOMO_WEBHOOK_SECRET', 'momo-test-secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Departure reminders
    |--------------------------------------------------------------------------
    |
    | Passengers holding an active booking are reminded when their trip departs
    | within this lead time (in hours).
    |
    */

    'reminder' => [
        'lead_hours' => (int) env('ALLODAKAR_REMINDER_LEAD_HOURS', 24),
    ],

];
