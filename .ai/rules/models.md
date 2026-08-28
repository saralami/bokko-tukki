---
paths:
  - app/Models/Trip.php
---

# Models

## Trajets: capacité/places dérivées du véhicule + publication
Trip.capacity et available_seats sont DÉRIVÉS du véhicule (jamais saisis) : à la création capacity=available_seats=vehicle.capacity ; à l'update draft on réinitialise, sinon on clamp available_seats=min(actuel, vehicle.capacity). available_seats = unsignedSmallInteger + Trip::reserveSeats() qui lève RuntimeException si insuffisant → jamais négatif. Publication via App\Actions\PublishTrip (draft→published) qui vérifie véhicule actif, chauffeur actif, destinations actives, prix>0 (ValidationException sinon). departure_destination_id/arrival_destination_id = 2 FK destinations (point de départ + destination), restrictOnDelete. Destinations gérées par l'admin (Admin\DestinationController), sélectionnables seulement si status=active.
