-- ============================================================
-- Migration 05 : Lien parties ↔ sessions
-- ============================================================

ALTER TABLE parties
  ADD COLUMN session_id VARCHAR(128) DEFAULT NULL,
  ADD CONSTRAINT fk_parties_session
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL;
