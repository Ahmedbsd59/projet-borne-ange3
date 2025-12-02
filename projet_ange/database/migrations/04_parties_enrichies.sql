-- ============================================================
-- Migration 04 : Enrichissement de la table parties
-- Ajoute : deuxieme_chance, duree_partie, gain_libelle
-- ============================================================

ALTER TABLE parties
  ADD COLUMN deuxieme_chance BOOLEAN DEFAULT FALSE,
  ADD COLUMN duree_partie    INT DEFAULT NULL,
  ADD COLUMN gain_libelle    VARCHAR(200) DEFAULT NULL;
