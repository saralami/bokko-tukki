---
paths:
  - 'app/Http/Controllers/Driver/**'
---

# Driver

## Espace chauffeur : shell mobile-first + embarquement sécurisé par référence/QR
Pages `driver/*` utilisent DriverLayout (nav basse: Accueil, Trajets, Embarquement, Profil ; résolu par préfixe dans app.ts). Scoping: un chauffeur = User->driver (hasOne) ; trajets via Trip.driver_id === driver->id (jamais TripPolicy qui est transporteur-only). DashboardController rend un état vide `hasProfile:false` si pas de profil Driver (ne PAS abort 403 : casse RolePermissionTest). Embarquement: 3 entrées mais UN seul choke point = App\Actions\ConfirmBoarding (complete + boarded_at + ProcessCashPayment si cash + BoardingConfirmedNotification au passager + event BookingBoarded). Ne PAS rappeler ProcessCashPayment dans les contrôleurs (l'action le fait). Validation par référence = Driver\BoardingController@store : normalise la réf (regex AD-XXXXXXXX, gère un QR contenant une URL), sécurité via Gate::allows('confirmBoarding') (BookingPolicy autorise driver assigné OU transporteur), erreurs friendly via ValidationException clé 'reference'. QR = API navigateur BarcodeDetector (aucune dépendance ajoutée), fallback saisie manuelle. Signalement = TripIncident (scoping driver_id) + DriverIncidentReportedNotification au transporteur. Présentation via App\Support\DriverTripPresenter (aggregats reservations_count/booked_seats chargés par withCount/withSum).
