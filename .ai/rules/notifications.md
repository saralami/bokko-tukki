---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Notifications multi-canal + abstraction SMS découplée
Notifications = mécanisme Laravel. Toute notif étend App\Notifications\BaseNotification (subject()/content()/payload()) → rendu identique sur database (in-app), mail, sms. Canaux résolus par App\Support\NotificationChannels::for($notifiable) : database toujours + mail si email + sms si phone (WhatsApp = futur ajout ici). Canal SMS custom App\Notifications\Channels\SmsChannel enregistré via Notification::extend('sms', ...) dans AppServiceProvider. Le cœur métier NE dépend PAS d'un fournisseur : abstraction App\Contracts\SmsSender (bind config services.sms.driver → LogSmsSender par défaut ; FakeSmsSender en test ; Orange/Twilio/WhatsApp = nouveaux drivers). Notifiables : passager/transporteur = User, chauffeur = Driver (Notifiable + routeNotificationForSms=phone). Émission APRÈS commit dans les actions (garde wasRecentlyCreated pour l'idempotence : pas de re-notif sur retour idempotent). Rappel de départ = commande trips:send-departure-reminders (schedule hourly, config allodakar.reminder.lead_hours, colonne trips.departure_reminded_at anti-doublon).
