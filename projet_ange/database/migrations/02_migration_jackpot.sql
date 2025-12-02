-- ============================================================
-- MIGRATION : Ajout du jeu JACKPOT (pour BDD déjà existantes)
-- Ce fichier est ignoré sur une installation fraîche car
-- le schema.sql inclut déjà jackpot.
-- Borne Interactive — BTS CIEL IR — Lycée Baggio
-- ============================================================

-- 1. Ajouter 'jackpot' à l'ENUM si pas encore présent
ALTER TABLE jeux MODIFY COLUMN type_jeu ENUM('roue_chance', 'memory', 'quiz', 'jackpot') NOT NULL;

-- 2. Insérer le jeu Jackpot uniquement s'il n'existe pas
INSERT INTO jeux (nom, description, type_jeu)
SELECT 'Jackpot', 'Alignez 3 symboles identiques pour gagner une réduction !', 'jackpot'
WHERE NOT EXISTS (SELECT 1 FROM jeux WHERE type_jeu = 'jackpot');

-- 3. Ajouter les dotations du Jackpot si elles n'existent pas
SET @jackpot_id = (SELECT id FROM jeux WHERE type_jeu = 'jackpot' LIMIT 1);

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, '-5% sur votre prochain achat',     5.00, 30.00, -1
WHERE NOT EXISTS (SELECT 1 FROM dotations WHERE jeu_id = @jackpot_id);

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, '-8% sur votre prochain achat',     8.00, 25.00, -1
WHERE (SELECT COUNT(*) FROM dotations WHERE jeu_id = @jackpot_id) < 2;

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, '-10% sur votre prochain achat',   10.00, 20.00, -1
WHERE (SELECT COUNT(*) FROM dotations WHERE jeu_id = @jackpot_id) < 3;

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, '-15% sur votre prochain achat',   15.00, 15.00, 50
WHERE (SELECT COUNT(*) FROM dotations WHERE jeu_id = @jackpot_id) < 4;

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, '-20% sur votre prochain achat',   20.00, 10.00, 30
WHERE (SELECT COUNT(*) FROM dotations WHERE jeu_id = @jackpot_id) < 5;

INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, stock)
SELECT @jackpot_id, 'Perdu - Retentez votre chance !',  0.00,  0.00, -1
WHERE (SELECT COUNT(*) FROM dotations WHERE jeu_id = @jackpot_id) < 6;
