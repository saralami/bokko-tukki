---
paths:
  - 'app/Http/Controllers/Transporter/**'
  - app/Http/Controllers/Transporter/DashboardController.php
---

# Transporter

## Fleet controllers scoped to the owning transporter
Les controllers transporteur (Vehicle/Driver/Transporter) opèrent TOUJOURS sur $request->user()->transporter (helper transporterFor() → abort(403) si absent). index/create/store partent de ce transporteur ; edit/update/destroy/updateStatus sont autorisés par Policy (VehiclePolicy/DriverPolicy ownership via transporter_id, TransporterPolicy via user_id). Les FormRequest portent l'autorisation (authorize()=can(...)) et scopent driver_id via Rule::exists->where('transporter_id', ...). Véhicules = SoftDeletes ; chauffeurs = suppression physique (vehicles.driver_id nullOnDelete). Statuts : enums DriverStatus/VehicleStatus/TransporterStatus (castés).

## Dashboard transporteur: stats scopées, tolère l'absence d'entreprise
Transporter\DashboardController agrège des KPIs STRICTEMENT scopés au transporteur connecté : revenue/commissions = Payment where transporter_id + status=completed ; reservations/seats_sold = Booking whereHas('trip', transporter_id) ; seats_remaining = trips published available_seats ; debt/available_balance = wallet ; withdrawals = withdrawals paid ; derniers paiements = 5 derniers. IMPORTANT : si $request->user()->transporter est null (user rôle transporteur sans entreprise, cf RolePermissionTest), NE PAS abort(403) → rendre le dashboard avec hasCompany=false + stats à zéro (les autres pages de gestion abort(403) elles). Transporter\BookingController@index = réservations de ses trajets (whereHas trip.transporter_id), n'expose que le NOM du passager. Conflit de nom avec Passenger\BookingController → aliaser dans routes/web.php (TransporterBookingController).
