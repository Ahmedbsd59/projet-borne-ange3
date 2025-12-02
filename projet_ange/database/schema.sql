-- ============================================================
-- BORNE INTERACTIVE - Modélisation du Système de Stockage
-- Étudiant 3 - BTS CIEL Option IR
-- Lycée César Baggio - Partenaire : Adictiz
-- ============================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS borne_interactive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE borne_interactive;

-- ============================================================
-- TABLE : clients
-- Stocke les informations des clients/participants
-- ============================================================
CREATE TABLE IF NOT EXISTS clients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100) NOT NULL,
    prenom          VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    telephone       VARCHAR(20),
    date_naissance  DATE,
    code_barre      VARCHAR(50) UNIQUE,        -- code barre du ticket de caisse
    mot_de_passe    VARCHAR(255) NOT NULL,      -- hashé en bcrypt
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif           BOOLEAN DEFAULT TRUE,
    CONSTRAINT chk_email CHECK (email LIKE '%@%.%')
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : jeux
-- Liste des jeux disponibles sur la borne
-- ============================================================
CREATE TABLE IF NOT EXISTS jeux (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(150) NOT NULL,
    description TEXT,
    type_jeu    ENUM('roue_chance', 'memory', 'quiz', 'jackpot') NOT NULL,
    actif       BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : dotations
-- Lots et récompenses à gagner
-- ============================================================
CREATE TABLE IF NOT EXISTS dotations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    jeu_id      INT NOT NULL,
    libelle     VARCHAR(200) NOT NULL,
    valeur      DECIMAL(10,2),
    probabilite DECIMAL(5,2) NOT NULL,   -- probabilité en pourcentage (0.00 à 100.00)
    stock       INT DEFAULT 0,            -- -1 = illimité
    couleur     VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (jeu_id) REFERENCES jeux(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : parties
-- Historique de chaque partie jouée
-- ============================================================
CREATE TABLE IF NOT EXISTS parties (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT,                       -- peut être NULL si joueur anonyme
    jeu_id          INT NOT NULL,
    dotation_gagnee INT,                       -- NULL = pas de gain
    code_barre_scan VARCHAR(50),               -- code barre utilisé pour jouer
    date_partie     DATETIME DEFAULT CURRENT_TIMESTAMP,
    score           INT DEFAULT 0,
    gagne           BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (jeu_id)    REFERENCES jeux(id) ON DELETE CASCADE,
    FOREIGN KEY (dotation_gagnee) REFERENCES dotations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : codes_barres
-- Codes barres tickets de caisse scannés
-- ============================================================
CREATE TABLE IF NOT EXISTS codes_barres (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(100) NOT NULL UNIQUE,
    utilise     BOOLEAN DEFAULT FALSE,
    date_scan   DATETIME DEFAULT CURRENT_TIMESTAMP,
    client_id   INT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : administrateurs
-- Comptes administrateurs de la borne
-- ============================================================
CREATE TABLE IF NOT EXISTS administrateurs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'maintenance') DEFAULT 'maintenance',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : sessions
-- Gestion des sessions de connexion
-- ============================================================
CREATE TABLE IF NOT EXISTS sessions (
    id          VARCHAR(128) PRIMARY KEY,
    client_id   INT,
    admin_id    INT,
    ip_address  VARCHAR(45),
    date_debut  DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME,
    actif       BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)  REFERENCES administrateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DONNÉES DE TEST (jeux par défaut)
-- ============================================================
INSERT INTO jeux (nom, description, type_jeu) VALUES
('La Roue de la Chance',   'Tournez la roue pour gagner des réductions !',                  'roue_chance'),
('Memory Magasin',         'Retrouvez les paires de produits pour gagner !',                 'memory'),
('Quiz Fidélité',          'Répondez aux questions et gagnez des bons d achat',              'quiz'),
('Jackpot',                'Alignez 3 symboles identiques pour gagner une réduction !',      'jackpot');

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock, couleur) VALUES
-- Roue de la Chance (jeu_id = 1)
(1, 'Gourde',                          0.00, 25.00, 100, '#2563eb'),
(1, 'Stylo Baggio',                    0.00, 20.00, 200, '#7c3aed'),
(1, 'Clé USB 16 Go',                   0.00, 20.00, 100, '#0891b2'),
(1, 'Collier Baggio',                  0.00, 15.00, 100, '#d97706'),
(1, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1, '#374151'),
-- Memory Magasin (jeu_id = 2)
(2, 'Gourde',                          0.00, 25.00, 100, '#2563eb'),
(2, 'Stylo Baggio',                    0.00, 20.00, 200, '#7c3aed'),
(2, 'Clé USB 16 Go',                   0.00, 20.00, 100, '#0891b2'),
(2, 'Collier Baggio',                  0.00, 15.00, 100, '#d97706'),
(2, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1, '#374151'),
-- Quiz Fidélité (jeu_id = 3)
(3, 'Gourde',                          0.00, 25.00, 100, '#2563eb'),
(3, 'Stylo Baggio',                    0.00, 20.00, 200, '#7c3aed'),
(3, 'Clé USB 16 Go',                   0.00, 20.00, 100, '#0891b2'),
(3, 'Collier Baggio',                  0.00, 15.00, 100, '#d97706'),
(3, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1, '#374151'),
-- Jackpot (jeu_id = 4)
(4, 'Gourde',                          0.00, 25.00, 100, '#2563eb'),
(4, 'Stylo Baggio',                    0.00, 20.00, 200, '#7c3aed'),
(4, 'Clé USB 16 Go',                   0.00, 20.00, 100, '#0891b2'),
(4, 'Collier Baggio',                  0.00, 15.00, 100, '#d97706'),
(4, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1, '#374151');

INSERT INTO administrateurs (login, mot_de_passe, role) VALUES
('admin', '$2y$10$exampleHashedPasswordHere', 'admin'),
('maintenance', '$2y$10$exampleHashedPasswordHere', 'maintenance');

-- ============================================================
-- VUES UTILES
-- ============================================================

-- Vue : classement des gains par client
CREATE VIEW v_gains_clients AS
SELECT
    c.id,
    CONCAT(c.prenom, ' ', c.nom) AS client,
    c.email,
    COUNT(p.id)                  AS nb_parties,
    SUM(p.gagne)                 AS nb_gains,
    GROUP_CONCAT(d.libelle SEPARATOR ', ') AS gains_obtenus
FROM clients c
LEFT JOIN parties p ON p.client_id = c.id
LEFT JOIN dotations d ON p.dotation_gagnee = d.id
GROUP BY c.id;

-- Vue : statistiques des jeux
CREATE VIEW v_stats_jeux AS
SELECT
    j.nom AS jeu,
    COUNT(p.id)        AS nb_parties,
    SUM(p.gagne)       AS nb_gagnants,
    ROUND(SUM(p.gagne) / NULLIF(COUNT(p.id),0) * 100, 2) AS taux_gain_pct
FROM jeux j
LEFT JOIN parties p ON p.jeu_id = j.id
GROUP BY j.id;
