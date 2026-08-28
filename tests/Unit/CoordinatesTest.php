<?php

use App\Support\Coordinates;

test('the distance between identical points is zero', function () {
    $point = new Coordinates(14.6928, -17.4467);

    expect($point->distanceInKilometersTo($point))->toBe(0.0);
});

test('one degree of longitude at the equator is a great-circle degree', function () {
    $distance = (new Coordinates(0.0, 0.0))->distanceInKilometersTo(new Coordinates(0.0, 1.0));

    // pi/180 * 6371.0088 km = 111.19 km (exact great-circle, not an approximation)
    expect(round($distance, 1))->toBe(111.2);
});

test('one degree of latitude equals one great-circle degree', function () {
    $distance = (new Coordinates(0.0, 0.0))->distanceInKilometersTo(new Coordinates(1.0, 0.0));

    expect(round($distance, 1))->toBe(111.2);
});

test('the distance is symmetric', function () {
    $dakar = new Coordinates(14.6928, -17.4467);
    $saintLouis = new Coordinates(16.0179, -16.4896);

    expect($dakar->distanceInKilometersTo($saintLouis))
        ->toBe($saintLouis->distanceInKilometersTo($dakar));
});

test('the distance between Dakar and Saint-Louis is about 180 km', function () {
    $distance = (new Coordinates(14.6928, -17.4467))
        ->distanceInKilometersTo(new Coordinates(16.0179, -16.4896));

    expect($distance)->toBeGreaterThan(175.0)->toBeLessThan(185.0);
});

test('tryFrom returns null when a coordinate is missing', function () {
    expect(Coordinates::tryFrom(null, -17.4467))->toBeNull()
        ->and(Coordinates::tryFrom('', ''))->toBeNull()
        ->and(Coordinates::tryFrom(14.6928, -17.4467))->toBeInstanceOf(Coordinates::class);
});
