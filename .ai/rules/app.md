---
paths:
  - 'app/**'
---

# App

## Rôles/permissions via spatie + enum UserRole
Autorisation basée sur spatie/laravel-permission (v8). Les noms de rôles = valeurs de App\Enums\UserRole (passenger/driver/transporter/admin). Aliases middleware `role`/`permission`/`role_or_permission` dans bootstrap/app.php. Admin = super-admin via Gate::before dans AppServiceProvider. Isolation multi-tenant par Policies (ex: TransporterPolicy = ownership user_id). Nouveaux rôles : ajouter un case à l'enum + reseed RolesAndPermissionsSeeder. Factories: User::factory()->passenger()/driver()/transporter()/admin().

## Posture production : notifications en file, HTTPS forcé, docs de déploiement
Prod prep (Phase 13). BaseNotification implémente ShouldQueue (use Queueable) → un worker queue:work DOIT tourner en prod sinon aucune notification n'est envoyée (QUEUE_CONNECTION=database ; tests sync via phpunit donc OK). AppServiceProvider force URL::forceScheme('https') en production. Paramètres métier (commission/dette/annulation/rappel) : défauts dans .env (ALLODAKAR_*) mais surchargés à chaud via App\Support\Settings / backoffice /admin/settings. Secret webhook = ALLODAKAR_MOMO_WEBHOOK_SECRET (jamais commité ; .env.example le laisse vide). Index prod : migration add_production_indexes n'ajoute QUE payments.status (trips a déjà (status,departure_date) ; ne pas re-créer ; éviter un composite commençant par une colonne de FK — bloque le drop). Scheduler: trips:send-departure-reminders (routes/console.php) via cron schedule:run. Doc de déploiement + checklist go-live dans docs/DEPLOYMENT.md et docs/PRODUCTION_CHECKLIST.md (13 sections : install, env, migration, seed, worker, scheduler, storage, SSL, monitoring, backup, restore, deploy, rollback). optimize (config/route/event/view cache) fonctionne — routes cachables.
