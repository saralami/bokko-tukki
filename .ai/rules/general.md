---
paths:
  - '.env*'
---

# General

## DB dédiée MySQL `allodakar` (jamais `laravel`)
Dev/prod sur MySQL (Herd, 127.0.0.1:3306, root sans mdp). La base `DB_DATABASE=allodakar` est dédiée. Ne JAMAIS pointer sur la base `laravel` : elle appartient à un autre projet (tables bookings/excursions/travel_offers…). Les tests tournent sur sqlite in-memory (phpunit.xml), indépendant de MySQL.
