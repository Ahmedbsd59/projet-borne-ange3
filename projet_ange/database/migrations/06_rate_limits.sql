-- ================================================================
-- Migration 06 : Table rate_limits + tokens admin
-- ================================================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    ip                  VARCHAR(45)  NOT NULL,
    endpoint            VARCHAR(64)  NOT NULL,
    tentatives          INT          NOT NULL DEFAULT 1,
    derniere_tentative  DATETIME     NOT NULL DEFAULT (NOW()),
    bloque_jusqu_a      DATETIME     NULL,
    INDEX idx_ip_endpoint (ip, endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    token       VARCHAR(64)  NOT NULL UNIQUE,
    admin_id    INT          NOT NULL,
    role        VARCHAR(32)  NOT NULL,
    expire_a    DATETIME     NOT NULL,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
