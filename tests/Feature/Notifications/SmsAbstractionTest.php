<?php

use App\Contracts\SmsSender;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\User;
use App\Notifications\BookingCreatedNotification;
use App\Sms\FakeSmsSender;
use App\Sms\LogSmsSender;
use App\Support\NotificationChannels;

test('the SMS sender is resolved from a swappable abstraction', function () {
    expect(app(SmsSender::class))->toBeInstanceOf(LogSmsSender::class);
});

test('channels adapt to the notifiable contact details', function () {
    $user = User::factory()->passenger()->create(['phone' => '+221771112233']);
    expect(NotificationChannels::for($user))->toContain('database', 'mail', 'sms');

    $userWithoutPhone = User::factory()->passenger()->create(['phone' => null]);
    expect(NotificationChannels::for($userWithoutPhone))
        ->toContain('database', 'mail')
        ->not->toContain('sms');

    $driver = Driver::factory()->create(['phone' => '+221770000000']);
    expect(NotificationChannels::for($driver))
        ->toContain('database', 'sms')
        ->not->toContain('mail');
});

test('the SMS channel routes through the injected sender without touching business code', function () {
    $fake = new FakeSmsSender;
    $this->app->instance(SmsSender::class, $fake);

    $user = User::factory()->passenger()->create(['phone' => '+221771112233']);
    $booking = Booking::factory()->create();

    $user->notify(new BookingCreatedNotification($booking));

    expect($fake->messages)->toHaveCount(1)
        ->and($fake->messages[0]['to'])->toBe('+221771112233')
        ->and($fake->messages[0]['message'])->toContain($booking->reference);
});

test('an in-app notification is stored for the notifiable', function () {
    $user = User::factory()->passenger()->create();
    $booking = Booking::factory()->create();

    $user->notify(new BookingCreatedNotification($booking));

    expect($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data['title'])->toBe('Réservation créée')
        ->and($user->notifications()->first()->data['reference'])->toBe($booking->reference);
});
