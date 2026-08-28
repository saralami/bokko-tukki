# Allo Dakar — Checklist de mise en production

À valider (case cochée) **avant** l'ouverture au public. Voir `DEPLOYMENT.md`
pour les procédures détaillées.

## Configuration & environnement
- [ ] `APP_ENV=production` et `APP_DEBUG=false`
- [ ] `APP_KEY` généré (secret, jamais commité)
- [ ] `APP_URL` en `https://` correct
- [ ] `.env` présent uniquement sur le serveur — **aucun secret dans le dépôt**
- [ ] `.env.example` à jour (toutes les clés, sans valeurs sensibles)
- [ ] `php artisan config:cache && route:cache && event:cache && view:cache` sans erreur
- [ ] `APP_TIMEZONE=Africa/Dakar`, `APP_LOCALE=fr`

## Base de données & migrations
- [ ] User MySQL **dédié** (non-root), mot de passe fort, privilèges limités à `allodakar`
- [ ] `php artisan migrate --force` exécuté ; `migrate:status` tout « Ran »
- [ ] Index de production présents (`payments.status`, `trips(status, departure_date)`)
- [ ] Seed des rôles/permissions et des destinations réelles effectué
- [ ] Premier compte admin créé et sécurisé (2FA activée)
- [ ] Commandes destructrices bloquées en prod (vérifié)

## Sécurité
- [ ] TLS/SSL actif, redirection 80→443, HSTS
- [ ] Autorisation : policies actives (isolation inter-transporteurs testée)
- [ ] Middleware `role:*` sur toutes les zones (passenger/driver/transporter/admin)
- [ ] Comptes suspendus bloqués (`EnsureUserIsNotSuspended`)
- [ ] Rate limiting login/2FA/passkeys en place (Fortify)
- [ ] Mass assignment maîtrisé (`#[Fillable]`), pas de `request()->all()`
- [ ] Validation par Form Requests sur toutes les écritures
- [ ] Pas de secrets en clair dans les logs ; `LOG_LEVEL` = `warning`/`error`
- [ ] Journal d'audit opérationnel (`/admin/audit-logs`)
- [ ] En-têtes de sécurité (CSP/HSTS/X-Frame-Options) au niveau Nginx

## Finance (intégrité)
- [ ] Idempotence paiements vérifiée (`payments.booking_id` unique, `provider_reference` unique)
- [ ] Anti double-commission / double-retrait / double-remboursement (verrous + re-check) — tests de concurrence verts
- [ ] Ledger immuable ; corrections uniquement par écriture compensatoire justifiée
- [ ] Aucun endpoint d'édition directe d'une transaction

## Webhooks & paiements
- [ ] `ALLODAKAR_MOMO_WEBHOOK_SECRET` défini (secret partagé avec le fournisseur)
- [ ] URL webhook `POST /webhooks/mobile-money` accessible depuis le fournisseur (IP allowlist si possible)
- [ ] Endpoint exempté de CSRF mais protégé par secret (`hash_equals`) — vérifié
- [ ] Test d'un paiement Mobile Money de bout en bout en sandbox

## Files d'attente, scheduler, jobs
- [ ] Worker `queue:work` supervisé et démarré (Supervisor) ; `numprocs ≥ 2`
- [ ] `queue:restart` intégré au déploiement
- [ ] Cron `schedule:run` (chaque minute) actif ; `schedule:list` OK
- [ ] Surveillance de `failed_jobs`

## Stockage & logs
- [ ] `storage:link` exécuté
- [ ] `storage/` et `bootstrap/cache/` inscriptibles par `www-data`
- [ ] Rotation des logs configurée ; espace disque surveillé
- [ ] Disque S3 configuré si multi-serveurs / persistance requise

## Notifications
- [ ] SMTP réel configuré et testé (e-mails livrés)
- [ ] Passerelle SMS réelle (`SMS_DRIVER`) configurée et testée
- [ ] Notifications en file d'attente (worker requis) — vérifié

## Monitoring & sauvegardes
- [ ] Healthcheck `/up` branché à un moniteur externe
- [ ] Suivi des exceptions (Sentry/Flare) actif
- [ ] Sauvegarde DB quotidienne, chiffrée, **hors-site**
- [ ] **Restauration testée** au moins une fois
- [ ] Alerting (uptime, erreurs, backlog file, disque)

## CORS / API
- [ ] Application SPA same-origin (Inertia) — CORS non requis pour l'UI
- [ ] Si une API externe est ajoutée : publier `config/cors.php` et restreindre les origines

## Qualité (avant tag de release)
- [ ] `php artisan test` — suite complète verte
- [ ] `vendor/bin/pint --test` — style conforme
- [ ] `vendor/bin/phpstan analyse` — 0 erreur (niveau 7)
- [ ] `npm run build` — build front OK
- [ ] Tag Git de release créé + sauvegarde DB pré-déploiement

## Go / No-Go
- [ ] Smoke test post-déploiement : login, recherche, réservation, paiement, embarquement, backoffice
- [ ] Procédure de rollback validée et prête (§13 du guide)
