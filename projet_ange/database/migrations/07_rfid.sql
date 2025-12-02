-- ============================================================
-- MIGRATION 07 : Support RFID
-- Ajoute rfid_uid sur clients + table rfid_scans (file d'attente)
-- ============================================================
USE borne_interactive;

-- Colonne UID sur les clients (8-20 hex chars, ex: "A3F2C1B0")
ALTER TABLE clients
    ADD COLUMN IF NOT EXISTS rfid_uid VARCHAR(20) UNIQUE DEFAULT NULL;

-- Table de file d'attente : l'ESP8266 y insère le scan,
-- la borne web le consomme et le supprime.
CREATE TABLE IF NOT EXISTS rfid_scans (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    uid        VARCHAR(20) NOT NULL,
    scanne_le  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    consomme   TINYINT(1)  NOT NULL DEFAULT 0,
    INDEX idx_uid_consomme (uid, consomme),
    INDEX idx_scanne_le   (scanne_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
