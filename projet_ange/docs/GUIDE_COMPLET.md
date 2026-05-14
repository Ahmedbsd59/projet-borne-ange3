# 📚 GUIDE COMPLET — BorneInteract
## Projet BTS CIEL Option IR — Lycée César Baggio × Adictiz
### Session 2025-2026 | Étudiant 3 — Périmètre complet

---

## 📋 TABLE DES MATIÈRES

1. [Présentation du projet](#1-présentation)
2. [Architecture générale](#2-architecture)
3. [Structure des fichiers](#3-structure-fichiers)
4. [Base de données](#4-base-de-données)
5. [Dashboard Admin temps réel *(NOUVEAU)*](#5-dashboard-admin)
6. [Application Mobile v2 *(NOUVEAU)*](#6-application-mobile-v2)
7. [Jeux en ligne](#7-jeux-en-ligne)
8. [Formulaire d'inscription](#8-formulaire-dinscription)
9. [Scanner code barre](#9-scanner-code-barre)
10. [API PHP](#10-api-php)
11. [Sécurité & conformité ANSSI](#11-sécurité--anssi)
12. [Installation & déploiement](#12-installation)
13. [Temps réel — évolutions possibles](#13-temps-réel)

---

## 1. PRÉSENTATION

### Contexte
Borne interactive phygitale en magasin. Le client scanne son **ticket de caisse** (QR Code, code barre 1D, badge RFID ou jeton physique) pour déclencher un jeu et tenter de gagner des réductions ou bons cadeaux.

### Bilan Étudiant 3

| # | Tâche | Fichier principal | Statut |
|---|-------|-------------------|--------|
| 1 | Modélisation BDD | `base_de_donnees/schema.sql` | ✅ |
| 2 | Jeux en ligne (×3) | `jeux/` | ✅ |
| 3 | Application mobile | `application_mobile/index.html` | ✅ v2 |
| 4 | Formulaire inscription | `formulaire_compte/inscription.html` | ✅ |
| 5 | Scanner code barre | `lecteur_code_barre/scanner.html` | ✅ |
| 6 | API PHP | `api/` | ✅ |
| **7** | **Dashboard Admin** | **`admin/dashboard.html`** | **✅ NOUVEAU** |
| **8** | **App Mobile redesignée** | **`application_mobile/index.html`** | **✅ NOUVEAU** |

---

## 2. ARCHITECTURE

```
┌─────────────────────────────────────────────────────┐
│                 NAVIGATEUR / PWA                    │
│  ┌──────────┐  ┌───────────┐  ┌──────────────────┐ │
│  │ App Mobile│  │  Jeux Web │  │ Dashboard Admin  │ │
│  └────┬─────┘  └─────┬─────┘  └────────┬─────────┘ │
└───────┼──────────────┼─────────────────┼────────────┘
        │              │                 │
        ▼              ▼                 ▼
┌──────────────────────────────────────────────────────┐
│         Apache / Nginx (XAMPP en dev)                │
│              PHP 8.x  +  PDO                         │
├─────────────┬────────────┬─────────────┬─────────────┤
│ inscription │ verif_code │ enreg_partie│ stats_admin │
│    .php     │   _barre   │    .php     │   .php (*)  │
└─────────────┴─────┬──────┴─────────────┴─────────────┘
                    │
        ┌───────────▼────────────┐
        │   MySQL  borne_inter.  │
        │  6 tables + 2 vues     │
        └───────────┬────────────┘
                    │
        ┌───────────▼────────────┐
        │  ESP8266 / ESP32-CAM   │
        │  Capteurs IoT, RFID,   │
        │  Buzzer, LEDs          │
        └────────────────────────┘
(*) à créer pour le temps réel en production
```

---

## 3. STRUCTURE DES FICHIERS

```
projet_etudiant3/
│
├── index.html                         ← Portail d'accueil global
│
├── admin/                             ← 🆕 Espace administrateur
│   ├── index.html                     ← Login admin (admin / admin123)
│   └── dashboard.html                 ← Dashboard complet temps réel
│
├── application_mobile/
│   └── index.html                     ← 🆕 App PWA redesignée v2
│
├── base_de_donnees/
│   ├── schema.sql                     ← 6 tables + vues + données test
│   └── README_BDD.md                  ← Diagramme ERD
│
├── jeux/
│   ├── roue_chance.html               ← Canvas HTML5, 8 secteurs
│   ├── memory.html                    ← Grille 4×4, flip 3D CSS
│   └── quiz.html                      ← QCM 10 questions
│
├── formulaire_compte/
│   └── inscription.html               ← 3 étapes + validation live
│
├── lecteur_code_barre/
│   └── scanner.html                   ← Caméra + saisie manuelle
│
├── api/
│   ├── db.php                         ← Connexion PDO MySQL
│   ├── inscription.php                ← POST création compte client
│   ├── verifier_code_barre.php        ← POST validation ticket
│   └── enregistrer_partie.php         ← POST sauvegarde partie
│
└── documentation/
    └── GUIDE_COMPLET.md               ← Ce document
```

---

## 4. BASE DE DONNÉES

### Tables

```sql
clients         (id, prenom, nom, email, telephone, date_naissance,
                 mot_de_passe_hash, points_fidelite, niveau_fidelite,
                 date_inscription, actif)

jeux            (id, nom, description, type_jeu, actif,
                 probabilite_gain, dotation_principale)

dotations       (id, id_jeu, libelle, valeur, stock_disponible, probabilite)

parties         (id, id_client, id_jeu, id_code_barre, resultat,
                 dotation_gagnee, score, duree_secondes, date_partie)

codes_barres    (id, code, montant_achat, valide, utilise,
                 date_utilisation, id_client)

administrateurs (id, login, mot_de_passe_hash, role, derniere_connexion)
```

### Vues SQL
```sql
-- Top clients + total gains
SELECT * FROM v_gains_clients;

-- Stats par jeu (parties, taux gain, gain moyen)
SELECT * FROM v_stats_jeux;
```

### Niveaux de fidélité automatiques
| Niveau | Seuil points |
|--------|-------------|
| Bronze | 0 – 499 |
| Silver | 500 – 1 499 |
| Gold | 1 500 – 4 999 |
| Platinum | 5 000+ |

---

## 5. DASHBOARD ADMIN

### Accès
```
URL  : http://localhost/projet_etudiant3/admin/
Login: admin      MDP: admin123
Login: maintenance MDP: maint2025
```

> ⚠️ **Production** : remplacer par authentification PHP + `session_regenerate_id()` + bcrypt.

### Sections

#### 5.1 Dashboard (vue globale)
- **4 KPI** actualisés toutes les 2 secondes :
  - Participations totales
  - Nouveaux leads
  - Taux de gain (vs objectif 70 %)
  - Statut borne : ONLINE / uptime %
- **Mini-stats** : Scans QR, Badges RFID, Sessions actives
- **Graphique barre** activité hebdomadaire (filtrable semaine/mois)
- **Distribution gains** : -20%, Produit gratuit, Points fid., Perdu
- **Tableau derniers participants** : heure, email, méthode, résultat
- **Flux activité temps réel** : événements en continu (polling 2 s)

#### 5.2 Leads & Clients
- Tableau complet avec recherche, filtres (Aujourd'hui / Semaine / Mois)
- Colonnes : ID, prénom, nom, email, méthode scan, jeu joué, résultat, date

#### 5.3 Gestion Jeux
- Stats par jeu (Roue, Memory, Quiz) : parties, score moyen
- Distribution gains par jeu
- Tableau de paramétrage : dotation principale, probabilité, stock

#### 5.4 Surveillance IoT
- 2 flux caméras ESP32-CAM simulés (+ horodatage live)
- Journal des alertes de sécurité
- Capteurs IoT en direct : température, humidité, éclairage LED, buzzer

#### 5.5 Alertes ANSSI
- Tableau de conformité par domaine (IoT, Web App, Réseau, Données)
- Statut : ✅ Conforme / ⚠️ Partiel / ❌ À faire

#### 5.6 Journaux
- Log de tous les événements (INFO / WARN / ERROR / DEBUG)
- Flux en continu, 50 entrées gardées

#### 5.7 Paramètres
- Bascules ON/OFF : veille, LED, son, caméra, maintenance
- Configuration réseau : IP borne, passerelle, DNS, WiFi, BDD

---

## 6. APPLICATION MOBILE V2

### Fichier
`application_mobile/index.html`

### Design BorneInteract
- Palette : fond `#0D0D14`, violet `#7C3AED`, rose `#EC4899`, vert `#10B981`
- Typographie : **Inter** (Google Fonts)
- Dynamic Island simulé, barre de statut live
- Carte fidélité dégradée (violet → rose), points mis à jour dynamiquement

### Navigation — 5 onglets

| Onglet | Contenu |
|--------|---------|
| 🏠 Accueil | Carte fidélité, 3 actions rapides, derniers gains |
| 🎮 Jeux | Roue / Memory / Quiz avec stats et bouton jouer |
| 📷 Scanner | Caméra simulée + saisie manuelle + codes récents |
| 🎁 Cadeaux | Bons actifs, points, historique |
| 👤 Profil | 6 stats, menus compte/fidélité/support |

### Fonctionnalités
- Points fidélité mis à jour toutes les 5 secondes (simulation)
- Scanner : détection valide / déjà utilisé / inconnu selon dernier chiffre du code
- Codes récents en chips cliquables
- Toasts de notification

### Compatibilité
- PWA-ready (`apple-mobile-web-app-capable`)
- Responsive max-width 430px
- Compatible iOS 14+ et Android 8+

---

## 7. JEUX EN LIGNE

### Roue de la Chance
- Canvas HTML5, 8 secteurs colorés
- Animation ease-out quartic, rotation aléatoire pondérée
- Confetti au gain
- Appel API `enregistrer_partie.php` à la fin

### Memory Magasin
- Grille 4×4, 8 paires
- Chrono 60 secondes
- Score = f(temps restant, nombre de coups)
- Flip 3D CSS natif

### Quiz Fidélité
- 10 questions QCM
- Feedback immédiat (vert/rouge)
- Gain selon score : ≥ 5 → -5%, ≥ 7 → -10%, ≥ 9 → Bon 15€

---

## 8. FORMULAIRE D'INSCRIPTION

### Étapes
| Étape | Champs | Validation |
|-------|--------|------------|
| 1 | Prénom, Nom, DDN, Téléphone | DDN ≥ 16 ans, tél. format FR |
| 2 | Email, Mot de passe, Confirmation | Email unique, force mdp |
| 3 | Code barre ticket, CGU | CGU obligatoires |

### Force du mot de passe
- Faible (rouge) : < 6 caractères
- Moyen (orange) : ≥ 6 avec mixte
- Fort (vert) : ≥ 8 + majuscule + chiffre + spécial

---

## 9. SCANNER CODE BARRE

### Modes
1. **Caméra** : intégration QuaggaJS ou html5-qrcode (simulée en démo)
2. **Saisie manuelle** : 8–14 chiffres, clavier numérique

### Intégration QuaggaJS (production)
```javascript
Quagga.init({
  inputStream: {
    type: "LiveStream",
    target: document.querySelector('#viewfinder'),
    constraints: { facingMode: "environment" }
  },
  decoder: {
    readers: ["ean_reader", "ean_8_reader", "code_128_reader"]
  }
}, function(err) {
  if (err) { console.error(err); return; }
  Quagga.start();
});

Quagga.onDetected(function(result) {
  doScan(result.codeResult.code);
});
```

### Résultats possibles
| Dernier chiffre du code | Résultat |
|------------------------|---------|
| Multiple de 3 | ❌ Code inconnu |
| Pair (non mult. 3) | ✅ Valide → choix jeu |
| Impair (non mult. 3) | ⚠️ Déjà utilisé |

---

## 10. API PHP

### Endpoints

```
POST /api/inscription.php
     Body: { prenom, nom, email, telephone, date_naissance, mot_de_passe, code_barre }
     Return: { success, client_id, message }

POST /api/verifier_code_barre.php
     Body: { code_barre }
     Return: { valide, message, id_code, montant }

POST /api/enregistrer_partie.php
     Body: { id_client, id_jeu, id_code_barre, resultat, score, duree }
     Return: { success, partie_id, points_gagnes }
```

### Sécurité API
```php
// Exemple PDO préparé (injection SQL impossible)
$stmt = $pdo->prepare("SELECT * FROM codes_barres WHERE code = ? AND valide = 1");
$stmt->execute([$code]);

// Hash bcrypt
password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);

// CORS
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
```

---

## 11. SÉCURITÉ & ANSSI

| Domaine | Règle | Implémentation | Statut |
|---------|-------|----------------|--------|
| Web | Injection SQL | PDO prepared statements | ✅ |
| Web | XSS | `htmlspecialchars()` | ✅ |
| Web | CSRF | Token session (à compléter) | ⚠️ |
| Web | Sessions | `session_regenerate_id()` | ✅ |
| Réseau | WiFi | WPA2-Enterprise | ✅ |
| IoT | Auth capteurs | Token HMAC (à renforcer) | ⚠️ |
| IoT | Chiffrement | TLS partiel | ⚠️ |
| Réseau | Segmentation | VLAN borne/LAN | ❌ |
| Données | Mots de passe | bcrypt cost 12 | ✅ |
| Données | Données sensibles | Chiffrement BDD | ✅ |

---

## 12. INSTALLATION

### Prérequis
- XAMPP ≥ 8.0 (Apache + MySQL + PHP)
- Navigateur moderne (Chrome 90+, Firefox 88+, Safari 14+)

### Étapes

```bash
# 1. Copier le projet
cp -r projet_etudiant3/ C:/xampp/htdocs/
# (Linux) cp -r projet_etudiant3/ /var/www/html/

# 2. Démarrer XAMPP
#    → Apache : ON
#    → MySQL  : ON

# 3. Créer la base de données
#    phpMyAdmin → Nouvelle BDD → "borne_interactive" → UTF8mb4
#    Importer : base_de_donnees/schema.sql

# 4. Configurer la connexion
```

```php
// api/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'borne_interactive');
define('DB_USER', 'root');          // ← Modifier
define('DB_PASS', '');              // ← Modifier
```

```
# 5. Accéder aux interfaces
http://localhost/projet_etudiant3/                        Portail
http://localhost/projet_etudiant3/admin/                  Dashboard Admin
http://localhost/projet_etudiant3/application_mobile/     App Mobile
http://localhost/projet_etudiant3/jeux/roue_chance.html   Roue
http://localhost/projet_etudiant3/jeux/memory.html        Memory
http://localhost/projet_etudiant3/jeux/quiz.html          Quiz
```

### Identifiants de démonstration
| Interface | Login | Mot de passe |
|-----------|-------|--------------|
| Admin | `admin` | `admin123` |
| Maintenance | `maintenance` | `maint2025` |

---

## 13. TEMPS RÉEL

Le dashboard utilise actuellement un **polling simulé (JS, 2 s)**. En production avec PHP + MySQL :

### Option A — Polling AJAX (recommandé BTS)
```javascript
// Ajouter dans dashboard.html
async function fetchLiveStats() {
  const data = await fetch('/api/stats_admin.php').then(r => r.json());
  document.getElementById('kpi-participations').textContent = data.participations;
  document.getElementById('kpi-leads').textContent = data.leads;
  // ...
}
setInterval(fetchLiveStats, 3000);
```

```php
// api/stats_admin.php (à créer)
<?php
require 'db.php';
$stmt = $pdo->query("SELECT COUNT(*) as total FROM parties");
$participations = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$leads = $stmt->fetchColumn();
echo json_encode(['participations' => $participations, 'leads' => $leads]);
```

### Option B — Server-Sent Events (SSE)
```php
// api/stream.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
while (true) {
    echo "data: " . json_encode(getLiveData()) . "\n\n";
    ob_flush(); flush();
    sleep(2);
}
```

```javascript
const es = new EventSource('/api/stream.php');
es.onmessage = e => updateDashboard(JSON.parse(e.data));
```

### Option C — WebSocket Ratchet (avancé, hors BTS)
```bash
composer require cboden/ratchet
php server.php   # Serveur WS sur ws://localhost:8080
```

---

## 📎 ANNEXES

### Codes de test scanner
| Code | Résultat attendu |
|------|-----------------|
| `37600501234566` (finit par 6) | ✅ Valide |
| `37600501234563` (finit par 3) | ❌ Inconnu |
| `37600501234561` (finit par 1) | ⚠️ Déjà utilisé |

### Codes promo (interface commande si activée)
| Code | Réduction |
|------|-----------|
| `CODE20` | -20% sur le total |
| `BAGGIO` | -2€ sur le total |

---

*BTS CIEL Option IR — Session 2025-2026*
*Lycée César Baggio (Lille) × Adictiz*
*Étudiant 3 — Mise à jour : Mars 2026*
