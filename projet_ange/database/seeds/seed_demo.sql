-- ============================================================
-- Données de démonstration — clients fictifs + parties jouées
-- Aujourd'hui = 2026-05-05
-- ============================================================

SET @mdp = '$2y$10$d6puk/GgAEj3/s37xoZZOuNbijgR83aeqSX5hNMpheEWJTNA2ETPi';

-- ── 10 clients fictifs ──────────────────────────────────────
INSERT IGNORE INTO clients (nom, prenom, email, telephone, date_naissance, mot_de_passe, date_inscription, actif) VALUES
('Dupont',   'Marie',   'marie.dupont@gmail.com',   '0612345601', '1995-03-14', @mdp, '2026-04-17 08:30:00', 1),
('Martin',   'Lucas',   'lucas.martin@orange.fr',   '0612345602', '1990-07-22', @mdp, '2026-04-19 10:15:00', 1),
('Benali',   'Sofia',   'sofia.benali@gmail.com',   '0612345603', '1998-11-05', @mdp, '2026-04-21 14:00:00', 1),
('Rousseau', 'Théo',    'theo.rousseau@hotmail.fr', '0612345604', '2001-01-30', @mdp, '2026-04-23 09:45:00', 1),
('Nguyen',   'Camille', 'camille.nguyen@gmail.com', '0612345605', '1993-09-17', @mdp, '2026-04-25 11:20:00', 1),
('Koné',     'Ibrahim', 'ibrahim.kone@sfr.fr',      '0612345606', '1988-04-02', @mdp, '2026-04-27 16:10:00', 1),
('Lefebvre', 'Emma',    'emma.lefebvre@laposte.net','0612345607', '2000-06-25', @mdp, '2026-04-29 10:05:00', 1),
('Girard',   'Maxime',  'maxime.girard@gmail.com',  '0612345608', '1985-12-11', @mdp, '2026-04-30 14:30:00', 1),
('Chaoui',   'Yasmine', 'yasmine.chaoui@gmail.com', '0612345609', '1997-08-08', @mdp, '2026-05-02 09:00:00', 1),
('Bernard',  'Antoine', 'antoine.bernard@gmail.com','0612345610', '1992-02-19', @mdp, '2026-05-04 08:50:00', 1);

-- ── Récupérer les IDs ────────────────────────────────────────
SET @marie   = (SELECT id FROM clients WHERE email = 'marie.dupont@gmail.com');
SET @lucas   = (SELECT id FROM clients WHERE email = 'lucas.martin@orange.fr');
SET @sofia   = (SELECT id FROM clients WHERE email = 'sofia.benali@gmail.com');
SET @theo    = (SELECT id FROM clients WHERE email = 'theo.rousseau@hotmail.fr');
SET @camille = (SELECT id FROM clients WHERE email = 'camille.nguyen@gmail.com');
SET @ibrahim = (SELECT id FROM clients WHERE email = 'ibrahim.kone@sfr.fr');
SET @emma    = (SELECT id FROM clients WHERE email = 'emma.lefebvre@laposte.net');
SET @maxime  = (SELECT id FROM clients WHERE email = 'maxime.girard@gmail.com');
SET @yasmine = (SELECT id FROM clients WHERE email = 'yasmine.chaoui@gmail.com');
SET @antoine = (SELECT id FROM clients WHERE email = 'antoine.bernard@gmail.com');

-- ── Parties (jeu 1=Roue, 2=Memory, 4=Jackpot) ───────────────
-- Dotations : Roue→ 1=-10%, 2=-20%, 3=Bon5€, 4=Perdu
--             Memory→ 5=Bon10€, 6=-15%, 7=Perdu
--             Jackpot→ 11=-5%, 12=-8%, 13=-10%, 14=-15%, 15=-20%, 16=Perdu

INSERT INTO parties (client_id, jeu_id, dotation_gagnee, gain_libelle, gagne, score, duree_partie, deuxieme_chance, date_partie) VALUES

-- Marie Dupont : joueuse régulière, 7 parties, bonne chance
(@marie, 1, 1,  '-10% sur votre prochain achat',  1,   0,  42, 0, '2026-04-18 09:23:00'),
(@marie, 2, 6,  '-15% sur votre prochain achat',  1, 580,  95, 0, '2026-04-20 14:07:00'),
(@marie, 1, 4,  'Perdu - Retentez votre chance !', 0,   0,  38, 1, '2026-04-22 10:45:00'),
(@marie, 4, 13, '-10% sur votre prochain achat',  1,   0,  18, 0, '2026-04-24 16:02:00'),
(@marie, 2, 5,  'Bon cadeau 10€',                 1, 640,  88, 0, '2026-04-28 11:33:00'),
(@marie, 1, 2,  '-20% sur votre prochain achat',  1,   0,  45, 0, '2026-05-02 09:10:00'),
(@marie, 4, 16, 'Perdu - Retentez votre chance !', 0,   0,  22, 0, '2026-05-04 15:50:00'),

-- Lucas Martin : régulier, résultats mitigés, 6 parties
(@lucas, 1, 4,  'Perdu - Retentez votre chance !', 0,   0,  35, 0, '2026-04-20 10:05:00'),
(@lucas, 1, 1,  '-10% sur votre prochain achat',  1,   0,  40, 1, '2026-04-21 14:18:00'),
(@lucas, 4, 12, '-8% sur votre prochain achat',   1,   0,  17, 0, '2026-04-23 09:41:00'),
(@lucas, 2, 7,  'Perdu - Retentez votre chance !', 0, 420, 120, 0, '2026-04-26 16:03:00'),
(@lucas, 1, 3,  'Bon cadeau 5€',                  1,   0,  43, 0, '2026-04-29 11:27:00'),
(@lucas, 4, 16, 'Perdu - Retentez votre chance !', 0,   0,  20, 0, '2026-05-03 13:55:00'),

-- Sofia Benali : découvre la borne, 3 parties
(@sofia, 2, 6,  '-15% sur votre prochain achat',  1, 510, 102, 0, '2026-04-22 10:12:00'),
(@sofia, 1, 4,  'Perdu - Retentez votre chance !', 0,   0,  39, 0, '2026-04-27 15:36:00'),
(@sofia, 4, 11, '-5% sur votre prochain achat',   1,   0,  16, 0, '2026-05-01 10:44:00'),

-- Théo Rousseau : très chanceux au Jackpot, 5 parties
(@theo, 4, 15,  '-20% sur votre prochain achat',  1,   0,  19, 0, '2026-04-24 09:07:00'),
(@theo, 4, 14,  '-15% sur votre prochain achat',  1,   0,  21, 0, '2026-04-26 14:28:00'),
(@theo, 1, 1,   '-10% sur votre prochain achat',  1,   0,  44, 0, '2026-04-28 10:55:00'),
(@theo, 4, 16,  'Perdu - Retentez votre chance !', 0,   0,  17, 0, '2026-04-30 11:14:00'),
(@theo, 4, 13,  '-10% sur votre prochain achat',  1,   0,  20, 0, '2026-05-03 16:38:00'),

-- Camille Nguyen : fan du Memory, 5 parties
(@camille, 2, 5,  'Bon cadeau 10€',                1, 720,  78, 0, '2026-04-25 13:20:00'),
(@camille, 2, 6,  '-15% sur votre prochain achat', 1, 610,  91, 0, '2026-04-27 09:05:00'),
(@camille, 2, 7,  'Perdu - Retentez votre chance !',0, 380, 135, 0, '2026-04-29 14:48:00'),
(@camille, 2, 5,  'Bon cadeau 10€',                1, 690,  82, 1, '2026-05-02 10:30:00'),
(@camille, 1, 3,  'Bon cadeau 5€',                 1,   0,  41, 0, '2026-05-04 11:15:00'),

-- Ibrahim Koné : moins chanceux, 4 parties
(@ibrahim, 1, 4,  'Perdu - Retentez votre chance !',0,   0,  37, 0, '2026-04-28 10:33:00'),
(@ibrahim, 2, 7,  'Perdu - Retentez votre chance !',0, 290, 148, 0, '2026-04-29 15:10:00'),
(@ibrahim, 4, 11, '-5% sur votre prochain achat',  1,   0,  18, 0, '2026-05-01 09:22:00'),
(@ibrahim, 1, 4,  'Perdu - Retentez votre chance !',0,   0,  40, 1, '2026-05-03 16:45:00'),

-- Emma Lefebvre : nouvelle, 3 premières parties
(@emma, 1, 1,   '-10% sur votre prochain achat',  1,   0,  43, 0, '2026-04-30 11:08:00'),
(@emma, 2, 7,   'Perdu - Retentez votre chance !', 0, 450, 112, 0, '2026-05-01 14:50:00'),
(@emma, 4, 12,  '-8% sur votre prochain achat',   1,   0,  21, 0, '2026-05-02 10:03:00'),

-- Maxime Girard : peu de temps disponible, 3 parties
(@maxime, 4, 14, '-15% sur votre prochain achat', 1,   0,  19, 0, '2026-05-01 12:17:00'),
(@maxime, 1, 4,  'Perdu - Retentez votre chance !',0,  0,  36, 0, '2026-05-02 09:40:00'),
(@maxime, 2, 6,  '-15% sur votre prochain achat', 1, 540,  98, 0, '2026-05-04 15:22:00'),

-- Yasmine Chaoui : inscrite il y a 3 jours, 3 parties
(@yasmine, 1, 2,  '-20% sur votre prochain achat', 1,   0,  46, 0, '2026-05-03 10:05:00'),
(@yasmine, 4, 16, 'Perdu - Retentez votre chance !',0,  0,  20, 0, '2026-05-04 14:30:00'),
(@yasmine, 2, 5,  'Bon cadeau 10€',                1, 660,  86, 0, '2026-05-05 09:45:00'),

-- Antoine Bernard : arrivé hier, très enthousiaste, 5 parties
(@antoine, 1, 4,  'Perdu - Retentez votre chance !',0,  0,  38, 0, '2026-05-04 10:12:00'),
(@antoine, 2, 6,  '-15% sur votre prochain achat', 1, 595,  94, 1, '2026-05-04 11:05:00'),
(@antoine, 4, 13, '-10% sur votre prochain achat', 1,   0,  22, 0, '2026-05-04 16:48:00'),
(@antoine, 1, 1,  '-10% sur votre prochain achat', 1,   0,  41, 0, '2026-05-05 09:30:00'),
(@antoine, 4, 15, '-20% sur votre prochain achat', 1,   0,  24, 0, '2026-05-05 10:15:00');
