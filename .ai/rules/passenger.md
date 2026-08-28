---
paths:
  - 'resources/js/pages/passenger/**'
---

# Passenger

## Passenger UX = shell mobile-first + parcours réservation→paiement→confirmation
Toutes les pages `passenger/*` utilisent PassengerLayout (barre de nav basse, résolu par préfixe dans resources/js/app.ts) — pas AppLayout. Parcours: Home(accueil, quick search) → Search(recherche+résultats) → TripDetails(détail) → bookings/Create(réservation: places + méthode) → POST store → Payment(bookings.payment) → bookings/Show(confirmation/détail). BookingController@store redirige vers passenger.bookings.payment (pas show). Le statut de paiement est réel: Payment n'existe qu'après mouvement d'argent (webhook Mobile Money OU cash à l'embarquement) → avant, la résa est "en attente"/"à régler à l'embarquement"; PAS de confirmation Mobile Money simulée (pas de fausses données). Présentation centralisée dans App\Support\BookingPresenter (summary/detail/paymentState). Badge notifications = prop Inertia partagée `unreadNotifications` (HandleInertiaRequests). Formatage FCFA/date via resources/js/lib/format.ts. Après ajout de page .vue: `npm run build`.
