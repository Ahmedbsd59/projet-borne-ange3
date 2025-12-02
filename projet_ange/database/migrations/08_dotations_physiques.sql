-- ============================================================
-- Migration 08 : Remplacement des lots par les 4 prix physiques
-- Gourde · Stylo Baggio · Clé USB 16 Go · Collier Baggio
-- ============================================================

-- Ajouter la colonne couleur si elle n'existe pas encore
ALTER TABLE dotations ADD COLUMN IF NOT EXISTS couleur VARCHAR(20) DEFAULT NULL;

-- Supprimer tous les anciens lots
DELETE FROM dotations;

-- ── Roue de la Chance (jeu_id = 1) ───────────────────────────
INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock, couleur) VALUES
(1, 'Gourde',                          0.00, 25.00, 100,  '#2563eb'),
(1, 'Stylo Baggio',                    0.00, 20.00, 200,  '#7c3aed'),
(1, 'Clé USB 16 Go',                   0.00, 20.00, 100,  '#0891b2'),
(1, 'Collier Baggio',                  0.00, 15.00, 100,  '#d97706'),
(1, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1,  '#374151');

-- ── Memory Magasin (jeu_id = 2) ──────────────────────────────
INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock, couleur) VALUES
(2, 'Gourde',                          0.00, 25.00, 100,  '#2563eb'),
(2, 'Stylo Baggio',                    0.00, 20.00, 200,  '#7c3aed'),
(2, 'Clé USB 16 Go',                   0.00, 20.00, 100,  '#0891b2'),
(2, 'Collier Baggio',                  0.00, 15.00, 100,  '#d97706'),
(2, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1,  '#374151');

-- ── Quiz Fidélité (jeu_id = 3) ───────────────────────────────
INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock, couleur) VALUES
(3, 'Gourde',                          0.00, 25.00, 100,  '#2563eb'),
(3, 'Stylo Baggio',                    0.00, 20.00, 200,  '#7c3aed'),
(3, 'Clé USB 16 Go',                   0.00, 20.00, 100,  '#0891b2'),
(3, 'Collier Baggio',                  0.00, 15.00, 100,  '#d97706'),
(3, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1,  '#374151');

-- ── Jackpot (jeu_id = 4) ─────────────────────────────────────
INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock, couleur) VALUES
(4, 'Gourde',                          0.00, 25.00, 100,  '#2563eb'),
(4, 'Stylo Baggio',                    0.00, 20.00, 200,  '#7c3aed'),
(4, 'Clé USB 16 Go',                   0.00, 20.00, 100,  '#0891b2'),
(4, 'Collier Baggio',                  0.00, 15.00, 100,  '#d97706'),
(4, 'Perdu - Retentez votre chance !', 0.00, 20.00,  -1,  '#374151');
