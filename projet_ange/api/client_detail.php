<?php
// ================================================================
// api/client_detail.php - Fiche d'un client avec toutes ses parties
// GET ?id=<client_id>
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

requireAdminAuth();

$clientId = (int)($_GET['id'] ?? 0);
if ($clientId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID client invalide']);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, prenom, nom, email,
               DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription,
               actif
        FROM clients WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client introuvable']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT
            p.id,
            DATE_FORMAT(p.date_partie, '%d/%m/%Y') AS date_partie,
            DATE_FORMAT(p.date_partie, '%H:%i')    AS heure,
            j.nom                                  AS jeu,
            CASE
                WHEN p.code_barre_scan IS NOT NULL THEN 'QR Code'
                ELSE 'RFID'
            END AS methode,
            COALESCE(p.gain_libelle, COALESCE(d.libelle, 'Perdu')) AS dotation,
            p.gagne,
            p.score,
            p.deuxieme_chance,
            p.duree_partie
        FROM parties p
        LEFT JOIN jeux      j ON p.jeu_id          = j.id
        LEFT JOIN dotations d ON p.dotation_gagnee = d.id
        WHERE p.client_id = ?
        ORDER BY p.date_partie DESC
        LIMIT 200
    ");
    $stmt->execute([$clientId]);
    $parties = $stmt->fetchAll();

    $wins   = array_sum(array_column($parties, 'gagne'));
    $total  = count($parties);

    echo json_encode([
        'success' => true,
        'client'  => $client,
        'parties' => $parties,
        'stats'   => [
            'total'        => $total,
            'wins'         => (int)$wins,
            'losses'       => $total - (int)$wins,
            'win_rate'     => $total > 0 ? round($wins / $total * 100) : 0,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
