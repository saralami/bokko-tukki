# Allo Dakar — Guide de déploiement production

Application Laravel 13 (PHP 8.4) + Inertia/Vue 3, base MySQL, files d'attente,
scheduler, paiements Mobile Money par webhook et notifications e-mail/SMS.

> ⚠️ **Aucun secret ne doit être commité.** Tous les secrets (APP_KEY, mot de
> passe DB, secret webhook Mobile Money, identifiants SMS/SMTP) vivent
> uniquement dans le fichier `.env` du serveur, jamais dans le dépôt.

Stack cible recommandée : Ubuntu 22.04+, Nginx, PHP-FPM 8.4, MySQL 8, Redis
(optionnel), Supervisor, Certbot (Let's Encrypt).

---

## 1. Installation

Prérequis serveur : PHP 8.4 (extensions `bcmath, ctype, curl, dom, fileinfo,
json, mbstring, openssl, pdo_mysql, tokenizer, xml`), Composer 2, Node 20+, MySQL 8.

```bash
# Récupérer le code (branche de production)
git clone <REPO_URL> /var/www/allodakar
cd /var/www/allodakar
git checkout main

# Dépendances PHP (sans dev) et build front
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Configuration
cp .env.example .env
php artisan key:generate        # génère APP_KEY (secret, reste sur le serveur)
```

Droits fichiers : le user PHP-FPM (`www-data`) doit posséder `storage/` et
`bootstrap/cache/` :

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

Optimisations production (à relancer à chaque déploiement) :

```bash
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
# équivalent groupé : php artisan optimize
```

---

## 2. Variables d'environnement

Éditer `/var/www/allodakar/.env`. Valeurs **obligatoires** en production :

| Variable | Valeur production |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://allodakar.sn` |
| `APP_KEY` | généré par `key:generate` (secret) |
| `DB_*` | hôte, base `allodakar`, user **dédié** non-root, mot de passe fort |
| `SESSION_DRIVER` | `database` (ou `redis`) |
| `QUEUE_CONNECTION` | `database` (ou `redis`) |
| `CACHE_STORE` | `database` (ou `redis`) |
| `MAIL_*` | SMTP réel (`MAIL_MAILER=smtp`) |
| `ALLODAKAR_MOMO_WEBHOOK_SECRET` | secret partagé avec le fournisseur Mobile Money |
| `SMS_DRIVER` | passerelle SMS réelle (Orange/Twilio/…) |

Paramètres métier (`ALLODAKAR_COMMISSION_RATE`, `ALLODAKAR_MAX_COMMISSION_DEBT`,
`ALLODAKAR_CANCELLATION_DEADLINE_HOURS`, `ALLODAKAR_REMINDER_LEAD_HOURS`) :
valeurs par défaut dans `.env`, **surchargeables à chaud** depuis le backoffice
admin (`/admin/settings`) sans redéploiement.

Après toute modification de `.env` : `php artisan config:cache`.

---

## 3. Migration

```bash
php artisan migrate --force        # --force requis en production (non interactif)
php artisan migrate:status         # vérifier l'état
```

Les commandes destructrices (`migrate:fresh`, `db:wipe`, `migrate:rollback`)
sont **bloquées en production** (`DB::prohibitDestructiveCommands`). Pour un
rollback contrôlé, voir §13.

Index de production inclus (agrégations financières, listings) — voir la
migration `add_production_indexes`.

---

## 4. Seed

Seed **indispensable** en production : rôles et permissions.

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=DestinationSeeder --force   # villes desservies (données réelles)
```

> Ne jamais exécuter `db:seed` global s'il génère des données de test/factices.
> N'insérer en production que des données réelles (rôles, destinations réelles).
> Créer le premier administrateur manuellement (Tinker) puis lui attribuer le
> rôle `admin`.

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name'=>'Admin','email'=>'admin@allodakar.sn','password'=>Hash::make(env('BOOTSTRAP_ADMIN_PW'))]);
>>> $u->assignRole('admin');
```

---

## 5. Queue worker

Les notifications (e-mail/SMS/in-app) et traitements asynchrones passent par la
file d'attente (`QUEUE_CONNECTION=database`). Un **worker doit tourner en
permanence**, supervisé.

`/etc/supervisor/conf.d/allodakar-worker.conf` :

```ini
[program:allodakar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/allodakar/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
directory=/var/www/allodakar
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/allodakar/storage/logs/worker.log
stopasgroup=true
killasgroup=true
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start allodakar-worker:*
```

À chaque déploiement, redémarrer les workers pour charger le nouveau code :
`php artisan queue:restart`.

---

## 6. Scheduler

Une seule entrée cron déclenche l'ordonnanceur Laravel (rappels de départ, etc.).

```cron
* * * * * cd /var/www/allodakar && php artisan schedule:run >> /dev/null 2>&1
```

Tâche planifiée : `trips:send-departure-reminders` (horaire) — voir
`routes/console.php`. Vérifier : `php artisan schedule:list`.

---

## 7. Stockage

```bash
php artisan storage:link          # lie public/storage -> storage/app/public
```

- Disque par défaut : `local`. Pour l'échelle, basculer `FILESYSTEM_DISK=s3`
  (renseigner `AWS_*`) afin que les fichiers survivent aux déploiements et soient
  partagés entre serveurs.
- `storage/` et `bootstrap/cache/` doivent rester inscriptibles par `www-data`.
- Logs : `storage/logs/` (rotation, voir §9).

---

## 8. SSL

TLS obligatoire (paiements, authentification). L'app force `https` en production
(`URL::forceScheme('https')`).

```bash
sudo certbot --nginx -d allodakar.sn -d www.allodakar.sn
```

Nginx : rediriger `80 -> 443`, transmettre `X-Forwarded-Proto https`. Vérifier
les `TrustProxies` si derrière un load balancer. Activer HSTS.

---

## 9. Monitoring

- **Logs applicatifs** : `storage/logs/laravel.log` (canal `stack`). En prod,
  régler `LOG_LEVEL=warning` (voire `error`) et envisager un canal `daily` ou un
  agrégateur (Sentry, Papertrail, CloudWatch).
- **Healthcheck** : endpoint `/up` (fourni par Laravel) — brancher un moniteur
  (UptimeRobot, Pingdom) dessus.
- **Queue** : surveiller la table `failed_jobs` et `storage/logs/worker.log` ;
  alerter si backlog. `php artisan queue:failed` pour lister, `queue:retry` pour rejouer.
- **Journal d'audit métier** : `/admin/audit-logs` (opérations sensibles).
- **Erreurs** : intégrer Sentry/Flare pour les exceptions non gérées.
- Surveiller CPU/RAM/disque du serveur et l'espace de `storage/logs`.

---

## 10. Sauvegarde DB

Sauvegarde quotidienne chiffrée, hors-serveur (S3 ou stockage distant).

```bash
#!/usr/bin/env bash
# /usr/local/bin/allodakar-backup.sh
set -euo pipefail
TS=$(date +%F_%H%M)
DIR=/var/backups/allodakar
mkdir -p "$DIR"
mysqldump --single-transaction --quick --routines \
  -u "$DB_USER" -p"$DB_PASS" allodakar | gzip > "$DIR/allodakar_$TS.sql.gz"
# Copie hors-site + purge > 30 jours
aws s3 cp "$DIR/allodakar_$TS.sql.gz" "s3://allodakar-backups/db/"
find "$DIR" -name '*.sql.gz' -mtime +30 -delete
```

Cron : `0 2 * * *` (2h du matin). Sauvegarder aussi `storage/app` si le disque
`local` est utilisé. **Tester régulièrement la restauration** (§11).

---

## 11. Restauration

```bash
# Depuis une sauvegarde (⚠️ écrase les données existantes)
gunzip < allodakar_2026-08-22_0200.sql.gz | mysql -u "$DB_USER" -p allodakar

# Puis remettre l'app en cohérence
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Procédure de reprise après incident : provisionner un serveur propre (§1),
restaurer la dernière sauvegarde, pointer le DNS, vérifier `/up` et un parcours
de réservation de bout en bout.

---

## 12. Déploiement

Déploiement sans coupure (zero-downtime conseillé via releases symlinkées).
Séquence minimale :

```bash
cd /var/www/allodakar
php artisan down --render="errors::503"    # maintenance (optionnel si zero-downtime)

git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan optimize        # config + route + view + event cache
php artisan storage:link || true
php artisan queue:restart   # recharge le worker avec le nouveau code

php artisan up
```

Toujours **sauvegarder la DB avant migration** (§10). Vérifier ensuite `/up`,
les logs, et un smoke test (login + recherche + réservation).

---

## 13. Rollback

En cas de régression après déploiement :

1. **Code** : revenir au commit/release précédent.
   ```bash
   git checkout <tag_precedent>   # ou basculer le symlink vers la release N-1
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan optimize
   php artisan queue:restart
   ```
2. **Base de données** :
   - Si la migration est réversible et sûre : `php artisan migrate:rollback --step=1 --force`.
   - Si une migration destructrice ou risquée est en cause : **restaurer la
     sauvegarde pré-déploiement** (§11) — c'est la voie sûre.
   - Ne jamais `migrate:fresh` en production (bloqué).
3. Vérifier `/up`, les workers (`supervisorctl status`), le scheduler
   (`schedule:list`) et un parcours critique.

> Règle d'or : toute release de production est précédée d'une sauvegarde DB et
> d'un tag Git, pour garantir un rollback rapide.
