#pragma once

// ============================================================
// config.h — Paramètres à adapter à ton installation
// ============================================================

// ── WiFi ─────────────────────────────────────────────────────
#define WIFI_SSID      "labo_snir"
#define WIFI_PASSWORD  "snbaggio123"

// ── Serveur ──────────────────────────────────────────────────
// IP de ton Mac sur le réseau local (partage de connexion)
#define API_HOST       "192.0.0.2"
#define API_PORT       80
#define API_PATH       "/api/rfid_scan.php"

// ── Broches RC522 → NodeMCU ──────────────────────────────────
//   RC522 SDA  → D4 (GPIO2)
//   RC522 SCK  → D5 (GPIO14)
//   RC522 MOSI → D7 (GPIO13)
//   RC522 MISO → D6 (GPIO12)
//   RC522 RST  → D3 (GPIO0)
//   RC522 GND  → GND
//   RC522 3.3V → 3.3V  ← NE PAS brancher sur 5V !
#define PIN_SDA  D2   // GPIO4 — stable au démarrage (D4/GPIO2 causait des conflits)
#define PIN_RST  D1   // GPIO5

// ── Broches TCS3200 → NodeMCU ────────────────────────────────
//   TCS3200 S0  → 3.3V  (fixé — mise à l'échelle 20%)
//   TCS3200 S1  → GND   (fixé — mise à l'échelle 20%)
//   TCS3200 S2  → D3 (GPIO0)
//   TCS3200 S3  → D4 (GPIO2)
//   TCS3200 OUT → D0 (GPIO16)
//   TCS3200 OE  → GND  (enable permanent)
//   TCS3200 VCC → 3.3V
#define TCS_S2   D3   // GPIO0 — sélection filtre couleur
#define TCS_S3   D4   // GPIO2 — sélection filtre couleur
#define TCS_OUT  D0   // GPIO16 — sortie fréquence

// Plages de fréquence pour le jeton officiel (rouge)
// Logique : jeton rouge → R faible, G et B élevés
// Valeurs à affiner après calibration avec le vrai jeton (pio device monitor)
#define JETON_R_MIN  0ul    // TODO : remplacer par valeur_min_R mesurée
#define JETON_R_MAX  200ul  // TODO : remplacer par valeur_max_R mesurée
#define JETON_G_MIN  200ul  // TODO : remplacer par valeur_min_G mesurée
#define JETON_G_MAX  999ul  // TODO : remplacer par valeur_max_G mesurée
#define JETON_B_MIN  200ul  // TODO : remplacer par valeur_min_B mesurée
#define JETON_B_MAX  999ul  // TODO : remplacer par valeur_max_B mesurée

// ── Comportement ─────────────────────────────────────────────
#define ANTI_REBOND_MS   3000   // délai minimum entre 2 scans du même badge
#define HTTP_TIMEOUT_MS  5000   // timeout requête HTTP
#define MONITOR_SPEED    115200
