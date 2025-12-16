<?php
// ================================================================
// api/stats_jeux.php - Stats détaillées par jeu pour la section Jeux
// GET → { success, jeux: [{id, nom, type_jeu, nb_parties, taux_gain, score_moyen, dotations:[]}] }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false]); exit;
}

requireAdminAuth();
header('Cache-Control: private, max-age=60');

try {
    $db = getDB();

    // Stats par jeu
    $stmt = $db->query("
        SELECT
            j.id,
            j.nom,
            j.type_jeu,
            COUNT(p.id)                                          AS nb_parties,
            COALESCE(SUM(p.gagne), 0)                           AS nb_gains,
            ROUND(COALESCE(SUM(p.gagne),0) / NULLIF(COUNT(p.id),0) * 100, 1) AS taux_gain,
            ROUND(AVG(NULLIF(p.score, 0)), 0)                   AS score_moyen,
            ROUND(AVG(NULLIF(p.duree_partie, 0)), 1)            AS duree_moyenne
        FROM jeux j
        LEFT JOIN parties p ON p.jeu_id = j.id
        WHERE j.actif = 1
        GROUP BY j.id
        ORDER BY j.id
    ");
    $jeux = $stmt->fetchAll();

    // Dotations par jeu
    $stmt2 = $db->query("
        SELECT
            d.jeu_id,
            d.libelle,
            d.probabilite,
            d.stock,
            d.couleur,
            COUNT(p.id) AS nb_gagne
        FROM dotations d
        LEFT JOIN parties p ON p.dotation_gagnee = d.id
        GROUP BY d.id
        ORDER BY d.probabilite DESC
    ");
    $dotations = $stmt2->fetchAll();

    // Indexer les dotations par jeu_id
    $dotByJeu = [];
    foreach ($dotations as $d) {
        $dotByJeu[(int)$d['jeu_id']][] = $d;
    }

    // Activité récente — 10 dernières parties avec client
    $stmt3 = $db->query("
        SELECT
            p.id,
            DATE_FORMAT(p.date_partie, '%H:%i') AS heure,
            DATE_FORMAT(p.date_partie, '%d/%m/%Y') AS date_partie,
            COALESCE(CONCAT(c.prenom, ' ', c.nom), 'Anonyme') AS client,
            j.nom AS jeu,
            COALESCE(p.gain_libelle, COALESCE(d.libelle, 'Perdu')) AS dotation,
            p.gagne
        FROM parties p
        LEFT JOIN clients   c ON p.client_id      = c.id
        LEFT JOIN jeux      j ON p.jeu_id          = j.id
        LEFT JOIN dotations d ON p.dotation_gagnee = d.id
        ORDER BY p.date_partie DESC
        LIMIT 20
    ");
    $recentes = $stmt3->fetchAll();

    // Assembler
    foreach ($jeux as &$j) {
        $j['nb_parties']   = (int)$j['nb_parties'];
        $j['nb_gains']     = (int)$j['nb_gains'];
        $j['taux_gain']    = (float)$j['taux_gain'];
        $j['score_moyen']  = $j['score_moyen'] ? (int)$j['score_moyen'] : null;
        $j['duree_moyenne']= $j['duree_moyenne'] ? (float)$j['duree_moyenne'] : null;
        $j['dotations']    = $dotByJeu[(int)$j['id']] ?? [];
    }

    echo json_encode([
        'success'  => true,
        'jeux'     => $jeux,
        'recentes' => $recentes,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
