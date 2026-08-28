---
paths:
  - app/Actions/SearchTrips.php
  - app/Actions/CreateBooking.php
---

# Actions

## Recherche trajets + distance (Haversine exact)
Recherche passager = App\Actions\SearchTrips (invocable). Filtrage en SQL (status=published, departure_date>=today, available_seats>=seats, destinations, date) ; distance/rayon/tri en PHP. Distance = App\Support\Coordinates::distanceInKilometersTo (Haversine exact, R=6371.0088km) — PAS d'approximation, DB-agnostique (tests SQLite OK, prod MySQL OK). Extensible : la distance est isolée, migrable vers ST_Distance_Sphere pour l'échelle. present() renvoie une shape curatée (company_name uniquement, brand/model, PAS de chauffeur/contacts) → ne jamais exposer de données privées. Détail public seulement si status=published (404 sinon). Astuce phpstan : trier avant de filtrer par rayon (le filtre !==null sur‑restreint le type sinon).

## Réservations: anti-overbooking, idempotence, cycle de vie
Créer une réservation UNIQUEMENT via App\Actions\CreateBooking : DB::transaction + Trip::lockForUpdate (sérialise la concurrence) + re-check available_seats avant decrement → jamais d'overbooking ni de places négatives. Idempotence via (passenger_id, idempotency_key) unique : la clé fait retourner la résa existante. Anti double-réservation : refus si résa active (pending/confirmed) déjà sur le trajet. Référence unique Booking::generateReference() ('AD-XXXXXXXX'). Annulation = App\Actions\CancelBooking (règle configurable config/allodakar.php cancellation.deadline_hours) : statut=cancelled + libère les places, JAMAIS de delete physique (audit financier). Embarquement = App\Actions\ConfirmBoarding (statut=completed + boarded_at) autorisé par BookingPolicy@confirmBoarding (transporteur propriétaire OU driver.user_id lié au trajet) — base du calcul des commissions (phase 6). Enums BookingStatus (pending/confirmed/cancelled/completed/no_show) & PaymentMethod (cash/mobile_money).
