#include <SPI.h>
#include <MFRC522.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>

// ======== CONFIGURATION ========
const char* ssid      = "labo_snir";
const char* password  = "snbaggio123";
// VERIFIE BIEN LE PORT 8081 ICI. Si c'est un serveur classique, retire ":8081"
const char* serverURL = "http://192.168.112.142/check.php";

// ======== BROCHAGES CORRIGÉS ========
#define SS_PIN    D1  // <-- CORRIGÉ : D4 pour libérer le SPI matériel
#define RST_PIN   D3  
#define LED_ROUGE D0  
#define LED_VERTE D2  

MFRC522 mfrc522(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);

  pinMode(LED_ROUGE, OUTPUT);
  pinMode(LED_VERTE, OUTPUT);
  digitalWrite(LED_ROUGE, LOW);
  digitalWrite(LED_VERTE, LOW);

  SPI.begin();
  mfrc522.PCD_Init();

  // Connexion Wi-Fi
  WiFi.begin(ssid, password);
  Serial.print("Connexion Wi-Fi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\n✅ Connecté ! IP ESP : " + WiFi.localIP().toString());
  Serial.println("Présentez une carte RFID...");
}

void clignoteRouge() {
  for (int i = 0; i < 3; i++) {
    digitalWrite(LED_ROUGE, HIGH);
    delay(200);
    digitalWrite(LED_ROUGE, LOW);
    delay(200);
  }
}

void loop() {
  // Attendre une carte
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(100);
    return;
  }

  // Lire l'UID
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  Serial.println("\n💳 Carte détectée - UID : " + uid);

  // Vérifier le Wi-Fi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ Wi-Fi déconnecté, reconnexion...");
    WiFi.begin(ssid, password);
    int tentatives = 0;
    while (WiFi.status() != WL_CONNECTED && tentatives < 10) {
      delay(500);
      tentatives++;
    }
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("❌ Impossible de se reconnecter au Wi-Fi");
      clignoteRouge();
      return;
    }
  }

  // Envoi HTTP
  WiFiClient client;
  HTTPClient http;
  String url = String(serverURL) + "?uid=" + uid;
  
  Serial.print("Envoi de la requête à : ");
  Serial.println(url);

  if (http.begin(client, url)) {
    int httpCode = http.GET();

    if (httpCode > 0) { // Si le code est positif, le serveur a répondu
      if (httpCode == HTTP_CODE_OK) {
        String reponse = http.getString();
        reponse.trim();
        Serial.println("Réponse serveur : " + reponse);

        if (reponse == "AUTORISE") {
          Serial.println("🔓 Accès autorisé !");
          digitalWrite(LED_VERTE, HIGH);
          delay(3000);
          digitalWrite(LED_VERTE, LOW);
        } else {
          Serial.println("🔒 Accès refusé !");
          clignoteRouge();
        }
      } else {
        // Erreur type 404 (Page non trouvée) ou 500 (Erreur PHP)
        Serial.printf("⚠️ Erreur HTTP du serveur : %d (%s)\n", httpCode, http.errorToString(httpCode).c_str());
        clignoteRouge();
      }
    } else {
      // Erreur négative : impossible d'atteindre le serveur (mauvaise IP, serveur éteint...)
      Serial.printf("❌ Échec de connexion au serveur : %s\n", http.errorToString(httpCode).c_str());
      clignoteRouge();
    }
    http.end();
  } else {
    Serial.println("❌ Impossible de formater la connexion HTTP");
  }

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  delay(1500);
}