// ============================================================
// ESP32-CAM — Vidéosurveillance BorneInteract
// Modèle : AI Thinker (OV2640)
//
// Branchement FTDI pour le flash :
//   ESP32-CAM 5V    → FTDI VCC
//   ESP32-CAM GND   → FTDI GND
//   ESP32-CAM U0R   → FTDI TX
//   ESP32-CAM U0T   → FTDI RX
//   ESP32-CAM GPIO0 → GND  (mode flash uniquement, débrancher après)
//
// Accès flux vidéo : http://[IP-ESP32-CAM]/stream
// ============================================================

#include "esp_camera.h"
#include <WiFi.h>
#include "esp_http_server.h"

// ── Modèle AI Thinker ────────────────────────────────────────
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

// ── Configuration à adapter ──────────────────────────────────
const char* WIFI_SSID     = "NETGEAR56";
const char* WIFI_PASSWORD = "rusticshrub645";

// ─────────────────────────────────────────────────────────────
// Streaming MJPEG — envoi continu des frames au navigateur
// ─────────────────────────────────────────────────────────────
#define PART_BOUNDARY "123456789000000000000987654321"
static const char* STREAM_CONTENT_TYPE =
    "multipart/x-mixed-replace;boundary=" PART_BOUNDARY;
static const char* STREAM_BOUNDARY = "\r\n--" PART_BOUNDARY "\r\n";
static const char* STREAM_PART =
    "Content-Type: image/jpeg\r\nContent-Length: %u\r\n\r\n";

httpd_handle_t stream_httpd = NULL;

esp_err_t stream_handler(httpd_req_t* req) {
    camera_fb_t* fb = NULL;
    esp_err_t    res = ESP_OK;
    char         part_buf[64];

    res = httpd_resp_set_type(req, STREAM_CONTENT_TYPE);
    if (res != ESP_OK) return res;

    while (true) {
        fb = esp_camera_fb_get();
        if (!fb) {
            Serial.println("Erreur capture frame");
            res = ESP_FAIL;
            break;
        }

        // Envoi de la boundary
        res = httpd_resp_send_chunk(req, STREAM_BOUNDARY,
                                    strlen(STREAM_BOUNDARY));
        if (res == ESP_OK) {
            size_t hlen = snprintf(part_buf, sizeof(part_buf),
                                   STREAM_PART, fb->len);
            res = httpd_resp_send_chunk(req, part_buf, hlen);
        }
        // Envoi de l'image JPEG
        if (res == ESP_OK) {
            res = httpd_resp_send_chunk(req,
                                        (const char*)fb->buf, fb->len);
        }

        esp_camera_fb_return(fb);
        if (res != ESP_OK) break;
    }
    return res;
}

void startCameraServer() {
    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.server_port = 80;

    httpd_uri_t stream_uri = {
        .uri       = "/stream",
        .method    = HTTP_GET,
        .handler   = stream_handler,
        .user_ctx  = NULL
    };

    if (httpd_start(&stream_httpd, &config) == ESP_OK) {
        httpd_register_uri_handler(stream_httpd, &stream_uri);
        Serial.println("Serveur streaming démarré sur /stream");
    }
}

// ─────────────────────────────────────────────────────────────
void setup() {
    Serial.begin(115200);
    Serial.println("\n=== BorneInteract — ESP32-CAM ===");

    // ── Configuration caméra ──────────────────────────────────
    camera_config_t config;
    config.ledc_channel = LEDC_CHANNEL_0;
    config.ledc_timer   = LEDC_TIMER_0;
    config.pin_d0       = Y2_GPIO_NUM;
    config.pin_d1       = Y3_GPIO_NUM;
    config.pin_d2       = Y4_GPIO_NUM;
    config.pin_d3       = Y5_GPIO_NUM;
    config.pin_d4       = Y6_GPIO_NUM;
    config.pin_d5       = Y7_GPIO_NUM;
    config.pin_d6       = Y8_GPIO_NUM;
    config.pin_d7       = Y9_GPIO_NUM;
    config.pin_xclk     = XCLK_GPIO_NUM;
    config.pin_pclk     = PCLK_GPIO_NUM;
    config.pin_vsync    = VSYNC_GPIO_NUM;
    config.pin_href     = HREF_GPIO_NUM;
    config.pin_sscb_sda = SIOD_GPIO_NUM;
    config.pin_sscb_scl = SIOC_GPIO_NUM;
    config.pin_pwdn     = PWDN_GPIO_NUM;
    config.pin_reset    = RESET_GPIO_NUM;
    config.xclk_freq_hz = 20000000;
    config.pixel_format = PIXFORMAT_JPEG;
    config.frame_size   = FRAMESIZE_VGA;  // 640×480
    config.jpeg_quality = 12;             // 0-63, plus bas = meilleure qualité
    config.fb_count     = 2;

    esp_err_t err = esp_camera_init(&config);
    if (err != ESP_OK) {
        Serial.printf("Erreur caméra : 0x%x\n", err);
        Serial.println("Vérifiez le câblage GPIO0 → GND retiré après flash");
        return;
    }
    Serial.println("Caméra OK !");

    // ── Connexion WiFi ────────────────────────────────────────
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    Serial.print("Connexion WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.print("\nWiFi OK — IP : ");
    Serial.println(WiFi.localIP());
    Serial.print("Flux vidéo accessible sur : http://");
    Serial.print(WiFi.localIP());
    Serial.println("/stream");

    startCameraServer();
}

void loop() {
    delay(10000); // rien à faire, le serveur HTTP tourne en arrière-plan
}
