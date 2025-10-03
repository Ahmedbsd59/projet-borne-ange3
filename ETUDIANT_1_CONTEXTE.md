# BorneInteract — Étudiant 1 : Contexte Complet

## Vue d'ensemble du projet
BorneInteract est une **borne interactive de jeux connectée** développée pour **Adictiz** (partenaire commercial). C'est un projet **BTS CIEL-IR 2ème année** (2025-2026) divisé entre 3 étudiants.

**Étudiant 1** est responsable de :
- Sécurité & infrastructure backend (Docker, BDD, API)
- Intégration hardware (RFID RC522, capteur couleur TCS3200, ESP8266)
- Jeu Memory en JavaScript
- Système 2ème chance
- Programmation ESP32-CAM pour vidéosurveillance

---

## Architecture générale

### Hardware
- **ESP8266 NodeMCU** : microcontrôleur central, gère RFID, TCS3200, WiFi
- **RC522** : lecteur RFID SPI pour badges clients
- **TCS3200** : capteur couleur pour détecter les jetons rouges
- **ESP32-CAM** : caméra de surveillance avec streaming MJPEG
- **WiFi** : connexion à l'API via réseau "pipoune" (192.0.0.2)

### Backend
- **Docker Compose** : MySQL 8.0 + PHP 8.2 Apache + phpMyAdmin
- **Base de données** : structure avec tables pour clients, parties, logs, gains
- **API REST** : endpoints pour RFID scan, jeton validation, enregistrement parties

### Frontend
- **Borne** : HTML5 + JavaScript (jeu Memory, choix jeux, affichage gains)
- **App Mobile** : Flutter (accès 2ème chance, QR code)

---

## Missions de l'Étudiant 1

### 1. Sécurité Base de Données
**Fichiers clés :**
- `partie_collective.docx` : documentation partagée
- API PHP (endpoints dans `/api/`)

**Accomplissements :**
- Hachage bcrypt pour tous les mots de passe (jamais en clair)
- Prepared statements PDO pour éviter SQL injection
- Tokens CSRF pour formulaires
- Rate limiting : 3 tentatives inscription/heure par IP
- Logs complets de tous les scans (timestamp, UID, résultat)

**Approche sécurité :**
- Jamais concaténer chaînes dans requêtes SQL
- Valider entrées au niveau API
- Stocker seulement hash passwords

---

### 2. Intégration RFID RC522

**Fichiers clés :**
- `projet_ange/hardware/src/main.cpp`
- `projet_ange/hardware/include/config.h`

**Pins ESP8266 → RC522 :**
```
SDA (D2/GPIO4) → RC522 SDA
SCK (D5/GPIO14) → RC522 SCK
MOSI (D7/GPIO13) → RC522 MOSI
MISO (D6/GPIO12) → RC522 MISO
RST (D1/GPIO5) → RC522 RST
```

**Accomplissements :**
- Détection badge RFID fonctionnelle
- Anti-rebond 3 secondes (évite double scan)
- Envoi UID à API `/api/rfid_scan.php`
- Gestion SPI correcte avec MFRC522 v1.4.12

**Points clés :**
- RC522 version 0x92 confirmée (init OK)
- Badge = identifiant client (qui es-tu ?)
- Indépendant du jeton (deux systèmes séparés)

---

### 3. Capteur Couleur TCS3200 & Jeton

**Fichiers clés :**
- `projet_ange/hardware/src/main.cpp` : fonction `jetonValide()`
- `TCS3200_Sketch/TCS3200_Sketch.ino` : sketch calibration optimisé
- `config.h` : plages R/G/B (JETON_R_MIN à JETON_B_MAX)

**Pins ESP8266 → TCS3200 :**
```
S2 (D3/GPIO0) → TCS3200 S2
S3 (D4/GPIO2) → TCS3200 S3
OUT (D0/GPIO16) → TCS3200 OUT
S0 → 3.3V (fixé)
S1 → GND (fixé)
OE → GND (toujours actif)
```

**Accomplissements :**
- Mesure RGB via changement filtres S2/S3
- Détection jeton ROUGE spécifique
- Envoi signal jeton à `/api/jeton_scan.php`
- Calibrage répété pour obtenir plages min/max fiables

**Points clés :**
- Jeton = interaction physique séparée du badge
- Valide = R faible ET G/B élevés
- Polling toutes les 2 secondes pour nouvelle détection

**Calibrage :**
Valeurs actuelles dans config.h (à affiner avec vrai jeton) :
```
JETON_R_MIN: 0, JETON_R_MAX: 200
JETON_G_MIN: 200, JETON_G_MAX: 999
JETON_B_MIN: 200, JETON_B_MAX: 999
```

---

### 4. Jeu Memory (HTML5 Canvas + JavaScript)

**Fichier clé :**
- `projet_ange/borne/jeux/memory.html`

**Accomplissements :**
- 8 paires d'emojis mélangées aléatoirement
- Algorithme Fisher-Yates pour shuffle O(n) uniforme
- État du jeu : `flipped[]`, `matched`, `moves`
- Détection paires avec `checkMatch()`
- Animation retournement cartes
- Enregistrement score BDD via `enregistrer_partie.php`
- Disponible borne ET app mobile

**Points clés :**
- Mécanique simple : clic → retourne carte
- 2 cartes retournées → vérification match
- Match trouvé = reste affichée
- Pas match = referme après 1 sec
- Gains associés : water bottle, pen, USB 16GB, Baggio necklace

---

### 5. Système 2ème Chance

**Concept :**
Client qui perd peut rejouer GRATUITEMENT via QR code + smartphone.

**Implémentation :**
- 1ère partie : INSERT nouvelle ligne table `parties`
- Si perte : client scanne QR code
- 2ème chance : UPDATE MÊME ligne (pas INSERT nouveau)
- Colonnes `dc_*` remplies : `dc_jeu_id`, `dc_gain_libelle`, `dc_gagne`, `dc_score`, `dc_date`

**Avantages :**
- 1 enregistrement = 2 résultats
- Pas jointures complexes
- Stats faciles à calculer
- Traçabilité claire

---

### 6. Programmation ESP32-CAM

**Spécifications :**
- Modèle : AI Thinker OV2640
- Streaming : MJPEG sur port 80
- URL : `http://[IP-ESP32]/stream`

**Défi programmation :**
- ESP32-CAM n'a pas USB direct
- Solution : ESP8266 comme programmateur FTDI
  1. RX/TX ESP8266 → RX/TX ESP32-CAM
  2. GPIO0 → GND (mode flash)
  3. Débrancher GPIO0 après upload

**Accomplissements :**
- Streaming temps réel fonctionnel
- Qualité JPEG configurable
- Surveillance optionnelle borne

---

## État actuel du firmware (optimisé)

### Compilation & Mémoire
```
RAM:   34.8% (28,480 / 81,920 bytes) ✅ STABLE
Flash: 27.8% (290,055 / 1,044,464 bytes)
```

**Avant optimisation :** IRAM 92% (critique) → **Après :** 34.8% (confortable)

### Optimisations appliquées
- Suppression 90% logs Serial.println()
- Utilisation PROGMEM pour strings
- Réduction délais (delay → delayMicroseconds)
- Réduction commentaires (code plus compacts)

### Fichiers clés
- `projet_ange/hardware/src/main.cpp` : firmware principal optimisé
- `TCS3200_Sketch/TCS3200_Sketch.ino` : sketch calibration TCS3200
- `BorneInteract.ino` : version Arduino IDE (fichier unique consolidé)

---

## WiFi & API

### Connectivité
- **SSID :** "labo_snir"
- **Password :** "snbaggio123"
- **API_HOST :** 192.0.0.2 (tethered connection)
- **API_PORT :** 80

### Endpoints API
1. `POST /api/rfid_scan.php` → body: `{"uid":"XXXXX"}`
2. `POST /api/jeton_scan.php` → body: `{"valide":true}`
3. `GET /api/jeton_scan.php` → vérifie s'il y a jeton

---

## Documentation externale

### Fichiers projet
- `Notes_Oral_Etudiant1.md` : 15 slides présentation oral + conseils timing
- `partie_collective.docx` : architecture générale partagée
- `generate_rapport.py` : génère rapport DOCX ~20 pages

### Oral BTS
- **Durée :** 15 minutes
- **Slides clés :** intro, missions, hardware, sécurité BDD, RFID, TCS3200, Memory, 2ème chance, ESP32-CAM, déploiement, défis, résultats
- **Démos possibles :** RFID scans en live, Memory gameplay, phpMyAdmin, logs API

---

## Défis surmontés

| Défi | Solution |
|------|----------|
| Calibrage TCS3200 | Mesures répétées (20+ fois) pour plages min/max |
| Anti-rebond RFID | Timer 3 secondes entre scans identiques |
| 2ème chance UX | UPDATE vs INSERT pour éviter duplication |
| Programmation ESP32-CAM | FTDI via ESP8266 + GPIO0→GND lors flash |
| Persistance Docker | Volumes persistants docker-compose.yml |
| IRAM ESP8266 92% | Suppression logs, optimisation mémoire → 34.8% |

---

## Prochaines étapes / À vérifier

- [ ] Calibrage TCS3200 avec jeton officiel rouge (affiner plages R/G/B)
- [ ] Test RFID en conditions réelles (distance, angle)
- [ ] Vérification 2ème chance end-to-end (QR code → PWA → UPDATE BDD)
- [ ] Streaming ESP32-CAM stable
- [ ] Performance server PHP sous charge (1000+ scans/jour)

---

## Contacts & Ressources

**Étudiant 1 :** [nom à ajouter]
**Partenaire :** Adictiz
**Année :** BTS CIEL-IR 2025-2026
**Date dernière maj :** 2026-06-04

---

## Notes pour futures sessions Claude

1. **Trois systèmes INDÉPENDANTS :** Badge RFID, Jeton TCS3200, Code-barres (Étudiant 3)
   - Ne pas les fusionner = architecture volontaire
   - Chacun envoie à son propre endpoint API

2. **Mémoire ESP8266 critique :**
   - IRAM max 65KB → chaque log coûte cher
   - Tester compilation avant upload
   - PROGMEM obligatoire pour strings longues

3. **WiFi fragile :**
   - Vérifier SSID/password dans config.h
   - API_HOST doit être accessible (curl test)
   - Mode offline graceful si WiFi perdu

4. **Docker Compose obligatoire :**
   - MySQL 8.0 + PHP 8.2 = env cible
   - Volumes persistants pour données
   - phpMyAdmin sur :8081 pour admin BDD

5. **Calibrage TCS3200 :**
   - Utiliser `TCS3200_Sketch.ino` pour tester seul
   - Lancer 20+ mesures avec jeton rouge présent
   - Noter min/max → MAJ config.h
