#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include "config.h"

// ── Objets globaux ────────────────────────────────────────────
MFRC522 rfid(PIN_SDA, PIN_RST);
WiFiClient wifiClient;

String   dernierUID  = "";
uint32_t dernierScan = 0;

// ── Prototypes ────────────────────────────────────────────────
void     connecterWifi();
String   lireUID();
bool     envoyerScan(const String& uid);
bool     envoyerJeton();
void     clignioterLED(int fois);
void     initTCS();
uint32_t lireCouleur(bool s2, bool s3);
bool     jetonValide();

// ── État jeton ───────────────────────────────────────────────
bool     jetonEnCours  = false;  // vrai si un jeton est en cours de validation
uint32_t dernierJeton  = 0;

// ─────────────────────────────────────────────────────────────
void setup() {
    Serial.begin(MONITOR_SPEED);
    pinMode(LED_BUILTIN, OUTPUT);
    digitalWrite(LED_BUILTIN, HIGH);

    initTCS();

    SPI.begin();
    rfid.PCD_Init();
    delay(100);

    byte version = rfid.PCD_ReadRegister(MFRC522::VersionReg);

    if (version == 0x00 || version == 0xFF) {
        Serial.println(F("RC522 ERR"));
        while (true) {
            digitalWrite(LED_BUILTIN, LOW);  delay(100);
            digitalWrite(LED_BUILTIN, HIGH); delay(100);
        }
    }
    Serial.println(F("OK"));

    connecterWifi();
}

void loop() {
    if (WiFi.status() != WL_CONNECTED) {
        connecterWifi();
    }

    uint32_t maintenant = millis();
    if (!jetonEnCours && (maintenant - dernierJeton) > 2000) {
        if (jetonValide()) {
            clignioterLED(2);
            envoyerJeton();
            jetonEnCours = true;
            dernierJeton  = maintenant;
        }
    }
    if (jetonEnCours && (maintenant - dernierJeton) > 3000) {
        jetonEnCours = false;
    }

    if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
        return;
    }

    String uid = lireUID();
    if (uid.isEmpty()) return;

    maintenant = millis();
    if (uid == dernierUID && (maintenant - dernierScan) < ANTI_REBOND_MS) {
        rfid.PICC_HaltA();
        return;
    }
    dernierUID  = uid;
    dernierScan = maintenant;

    Serial.println(uid);

    bool ok = envoyerScan(uid);
    clignioterLED(ok ? 3 : 1);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}

// ─────────────────────────────────────────────────────────────
// Connexion WiFi avec tentatives
// ─────────────────────────────────────────────────────────────
void connecterWifi() {
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    uint8_t tentatives = 0;
    while (WiFi.status() != WL_CONNECTED && tentatives < 20) {
        delay(500);
        tentatives++;
    }
}

// ─────────────────────────────────────────────────────────────
// Lire l'UID du badge et le retourner en hex majuscule
// ─────────────────────────────────────────────────────────────
String lireUID() {
    String uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uid += '0';
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    return uid;
}

// ─────────────────────────────────────────────────────────────
// Envoyer le scan à l'API PHP
// ─────────────────────────────────────────────────────────────
bool envoyerScan(const String& uid) {
    if (WiFi.status() != WL_CONNECTED) {
        return false;
    }

    HTTPClient http;
    String url = String("http://") + API_HOST + ":" + API_PORT + API_PATH;
    http.begin(wifiClient, url);
    http.addHeader(F("Content-Type"), F("application/json"));
    http.setTimeout(HTTP_TIMEOUT_MS);

    String body = "{\"uid\":\"" + uid + "\"}";
    int code = http.POST(body);
    http.end();
    return (code == 200);
}

// ─────────────────────────────────────────────────────────────
// Clignoter la LED intégrée
// ─────────────────────────────────────────────────────────────
void clignioterLED(int fois) {
    for (int i = 0; i < fois; i++) {
        digitalWrite(LED_BUILTIN, LOW);  delay(80);
        digitalWrite(LED_BUILTIN, HIGH); delay(80);
    }
}

// ─────────────────────────────────────────────────────────────
// Envoyer le signal jeton valide à l'API
// ─────────────────────────────────────────────────────────────
bool envoyerJeton() {
    if (WiFi.status() != WL_CONNECTED) return false;

    HTTPClient http;
    String url = String("http://") + API_HOST + ":" + API_PORT + "/api/jeton_scan.php";
    http.begin(wifiClient, url);
    http.addHeader(F("Content-Type"), F("application/json"));
    http.setTimeout(HTTP_TIMEOUT_MS);

    int code = http.POST("{\"valide\":true}");
    http.end();
    return (code == 200);
}

// ─────────────────────────────────────────────────────────────
// TCS3200 — Capteur couleur pour validation du jeton
// ─────────────────────────────────────────────────────────────
void initTCS() {
    pinMode(TCS_S2,  OUTPUT);
    pinMode(TCS_S3,  OUTPUT);
    pinMode(TCS_OUT, INPUT);
    // S0 et S1 sont câblés fixement (3.3V et GND) → pas de pinMode nécessaire
}

uint32_t lireCouleur(bool s2, bool s3) {
    digitalWrite(TCS_S2, s2);
    digitalWrite(TCS_S3, s3);
    delayMicroseconds(10000);
    return pulseIn(TCS_OUT, LOW, 100000);
}

// Retourne true si le jeton inséré correspond aux plages calibrées
bool jetonValide() {
    uint32_t r = lireCouleur(LOW,  LOW);
    uint32_t g = lireCouleur(HIGH, HIGH);
    uint32_t b = lireCouleur(LOW,  HIGH);

    return (r >= JETON_R_MIN && r <= JETON_R_MAX &&
            g >= JETON_G_MIN && g <= JETON_G_MAX &&
            b >= JETON_B_MIN && b <= JETON_B_MAX);
}
