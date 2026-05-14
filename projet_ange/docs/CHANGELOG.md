# Journal des modifications — BorneInteract
> BTS CIEL option IR — Lycée Baggio — Session 2026  
> Étudiants : DJELASSI Souhir / BENGORA Bouguerra / NEGGAOUI Ali

---

## Structure du projet

### Réorganisation des dossiers
| Avant | Après |
|-------|-------|
| `formulaire_compte/` | `inscription/` |
| `lecteur_code_barre/` | `scanner/` |
| `lecteur_rfid/` | `hardware/` |
| `documentation/` | `docs/` |
| `base_de_donnees/` | `database/` |
| `init/*.sql` | `database/migrations/` |
| — | `database/seeds/seed_demo.sql` |

### Fichiers ajoutés
- `.gitignore` — exclusion des fichiers sensibles (`.env`, `vendor/`, logs)
- `docs/CHANGELOG.md` — ce fichier

---

## Infrastructure Docker

### `Dockerfile`
- Suppression de la redirection HTTP → HTTPS (causait des erreurs `ERR_CONNECTION_REFUSED`)
- Apache sert désormais directement sur le port 80 (Tailscale gère HTTPS en amont)

### `docker-compose.yml`
- Mise à jour du volume MySQL : `./init` → `./database/migrations`

---

## API

### `api/db.php`
- Ajout des origines CORS autorisées :
  - `https://macbook-air-de-ahmed.tailfaf4e9.ts.net`
  - `https://borne.tailfaf4e9.ts.net`

### `api/stats.php`
- Correction du comptage `rfid_scans` (comptait les parties au lieu des clients avec badge)
- Ajout du paramètre `?periode=jour|mois|annee|tout`
- Retourne les 4 compteurs : `part_jour`, `part_mois`, `part_annee`, `part_tout`
- Ajout de `variation_parties` et `variation_leads` (comparaison semaine précédente)

### `api/mobile_login.php` *(nouveau)*
- Authentification mobile via `POST { email, password }`
- Génère un token HMAC-SHA256 stateless
- Retourne `{ success, token, client }`

### `api/mobile_profil.php` *(nouveau)*
- Récupération du profil via `GET Authorization: Bearer <token>`
- Retourne : client, stats (total/wins/losses/taux/points/rang), 20 dernières parties
- Calcul des points : victoires × 50 + défaites × 10
- Rangs : Bronze / Argent / Or / Platine

### `api/jeux_public.php` *(nouveau)*
- Liste publique des jeux sans authentification
- Retourne jeux avec `nb_parties`, `taux_gain`, `dotations`, `icon`, `colors`
- Cache 120 secondes

---

## Dashboard admin

### `admin/dashboard.html`
- Correction des valeurs initiales (0 au lieu de chiffres fictifs)
- Filtre période sur le KPI Participations (boutons ∞ / A / M / J)
- Correction de `loadStatsJeux()` : `addLog()` → `addFeedItem()`
- Correction de `startSimulation()` : ne corrompt les KPIs qu'en mode démo hors ligne
- Ajout de `refreshAll()` appelant `loadStatsJeux()` et `loadChartData()`

---

## Jeux

### `jeux/roue_chance.html` *(refait)*
- Design modernisé (thème violet/or cohérent)
- Dotations chargées depuis la base de données via `jeux_public.php`
- Résultats pondérés selon les probabilités de la BDD
- 8 segments toujours affichés (alternance gain/perdu, 50% de chance de perdre)
- Effets sonores synthétiques (tick par segment, fanfare si gagné)
- Confettis améliorés
- **Correction** : transmission du `code_barre` à `enregistrer_partie.php`

### `jeux/memory.html` *(amélioré)*
- 3 niveaux de difficulté : Facile (6 paires, 90s) / Normal (8 paires, 70s) / Difficile (10 paires, 50s)
- Système de score en temps réel
- Animation de tremblement sur erreur
- Effets sonores (flip, match, erreur, victoire/défaite)
- **Correction** : transmission du `code_barre` à `enregistrer_partie.php`

### `jeux/jackpot.html` *(nouveau)*
- 3 rouleaux animés avec défilement fluide et ralentissement progressif
- 5 parties par session
- Combinaisons gagnantes avec dotations selon le symbole (💎 20€, 7️⃣ 15€, etc.)
- Sons de machine à sous et fanfare jackpot
- Transmission du `code_barre` à `enregistrer_partie.php`

### `borne/index.html`
- `startGame()` redirige vers les fichiers `jeux/` améliorés au lieu des jeux intégrés
- Passage du code barre en paramètre URL (`?code=XXXXXX`) pour suppression après partie

---

## Application mobile PWA

### `application_mobile/index.html` *(nouveau)*
- 5 écrans : Connexion, Accueil, Jeux, Historique, Profil
- Carte fidélité avec points et rang en temps réel
- Filtres historique : tout / gagné / perdu / par type de jeu
- Gestion badge RFID

### `application_mobile/sw.js` *(nouveau)*
- Service Worker : réseau prioritaire pour `/api/`, cache pour l'app shell
- Fallback hors ligne

### `application_mobile/manifest.json` *(nouveau)*
- PWA installable sur iPhone (display: standalone)

---

## Application Flutter (Android)

### `borne_app/` *(nouveau projet)*
- Application Flutter native pour tablette/téléphone Android
- **Écrans** : Login, Accueil, Jeux, Historique, Profil
- Connectée à la vraie base de données via `https://borne.tailfaf4e9.ts.net/api`
- Carte fidélité avec points/rang, statistiques, historique avec filtres
- `BorneInteract.apk` généré et prêt à installer

---

## Données de test

### `database/seeds/seed_demo.sql` *(nouveau)*
- 10 clients fictifs avec mot de passe `Test123!`
- 44 parties réparties sur plusieurs semaines
- Badges RFID attribués à chaque client fictif

---

## Tailscale

- Hostname renommé : `macbook-air-de-ahmed` → `borne`
- URL publique : `https://borne.tailfaf4e9.ts.net`
- Funnel actif sur le port 80

---

## Accès rapide

| Service | URL locale | URL publique |
|---------|-----------|--------------|
| Borne interactive | http://127.0.0.1/borne/ | https://borne.tailfaf4e9.ts.net/borne/ |
| Dashboard admin | http://127.0.0.1/admin/ | https://borne.tailfaf4e9.ts.net/admin/ |
| Application mobile | http://127.0.0.1/application_mobile/ | https://borne.tailfaf4e9.ts.net/application_mobile/ |
| Roue de la chance | http://127.0.0.1/jeux/roue_chance.html | — |
| Memory | http://127.0.0.1/jeux/memory.html | — |
| Jackpot | http://127.0.0.1/jeux/jackpot.html | — |
| phpMyAdmin | http://127.0.0.1:8080 | — |
