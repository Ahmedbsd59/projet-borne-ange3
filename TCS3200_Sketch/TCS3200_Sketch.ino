#define TCS_S2  D3
#define TCS_S3  D4
#define TCS_OUT D0

uint32_t r = 0, g = 0, b = 0;
uint32_t lastPrint = 0;

#define JETON_R_MIN  0ul
#define JETON_R_MAX  200ul
#define JETON_G_MIN  200ul
#define JETON_G_MAX  999ul
#define JETON_B_MIN  200ul
#define JETON_B_MAX  999ul

void setup() {
    Serial.begin(115200);
    pinMode(TCS_S2, OUTPUT);
    pinMode(TCS_S3, OUTPUT);
    pinMode(TCS_OUT, INPUT);
    digitalWrite(TCS_S2, LOW);
    digitalWrite(TCS_S3, LOW);
}

void loop() {
    digitalWrite(TCS_S2, LOW);
    digitalWrite(TCS_S3, LOW);
    delayMicroseconds(20000);
    r = pulseIn(TCS_OUT, LOW, 1000000);

    digitalWrite(TCS_S2, HIGH);
    digitalWrite(TCS_S3, HIGH);
    delayMicroseconds(20000);
    g = pulseIn(TCS_OUT, LOW, 1000000);

    digitalWrite(TCS_S2, LOW);
    digitalWrite(TCS_S3, HIGH);
    delayMicroseconds(20000);
    b = pulseIn(TCS_OUT, LOW, 1000000);

    if (millis() - lastPrint >= 500) {
        Serial.print(r);
        Serial.print(" ");
        Serial.print(g);
        Serial.print(" ");
        Serial.println(b);
        lastPrint = millis();
    }

    if (r >= JETON_R_MIN && r <= JETON_R_MAX &&
        g >= JETON_G_MIN && g <= JETON_G_MAX &&
        b >= JETON_B_MIN && b <= JETON_B_MAX) {
        Serial.println(F("OK"));
    }

    delay(100);
}
