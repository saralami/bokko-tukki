<?php

namespace App\Models;

use App\Enums\DestinationStatus;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $city
 * @property string $region
 * @property float|null $latitude
 * @property float|null $longitude
 * @property DestinationStatus $status
 */
#[Fillable(['city', 'region', 'latitude', 'longitude', 'status'])]
class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => DestinationStatus::class,
        ];
    }

    /**
     * Scope the query to active destinations only.
     *
     * @param  Builder<Destination>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', DestinationStatus::Active);
    }
}
