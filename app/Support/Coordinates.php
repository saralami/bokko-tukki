<?php

namespace App\Support;

final class Coordinates
{
    /**
     * Mean Earth radius in kilometres used for the great-circle distance.
     */
    private const EARTH_RADIUS_KM = 6371.0088;

    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    /**
     * Build a set of coordinates from nullable values, returning null when incomplete.
     */
    public static function tryFrom(int|float|string|null $latitude, int|float|string|null $longitude): ?self
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        return new self((float) $latitude, (float) $longitude);
    }

    /**
     * Compute the exact great-circle distance to another point using the Haversine formula.
     */
    public function distanceInKilometersTo(self $other): float
    {
        $latFrom = deg2rad($this->latitude);
        $latTo = deg2rad($other->latitude);
        $latDelta = deg2rad($other->latitude - $this->latitude);
        $lonDelta = deg2rad($other->longitude - $this->longitude);

        $haversine = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($haversine)));
    }
}
