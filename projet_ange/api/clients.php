<?php
// ================================================================
// api/clients.php - Liste des clients pour la section Leads du dashboard
// Paramètres GET : limit (défaut 100), search (filtre email/nom)
// Réponse : { success, clients: [{id, prenom, nom, email, methode, jeu, dotation, gagne, date_inscription}] }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

requireAdminAuth();

$limit  = max(1, min(500, (int)($_GET['limit'] ?? 100)));
$search = trim($_GET['search'] ?? '');

try {
    $db = getDB();

    // Récupère chaque client avec sa dernière partie (jeu + dotation)
    $where  = '';
    $params = [];

    if ($search !== '') {
        $where    = "WHERE c.email LIKE :s OR c.nom LIKE :s OR c.prenom LIKE :s";
        $params[':s'] = '%' . $search . '%';
    }

    $sql = "
        SELECT
            c.id,
            c.prenom,
            c.nom,
            c.email,
            DATE_FORMAT(c.date_inscription, '%d/%m/%Y') AS date_inscription,
            -- Dernière partie de ce client
            COALESCE(j.nom, '')               AS jeu,
            COALESCE(d.libelle, 'Perdu')      AS dotation,
            COALESCE(p.gagne, 0)              AS gagne,
            CASE
                WHEN p.code_barre_scan IS NOT NULL THEN 'QR Code'
                ELSE 'RFID'
            END AS methode
        FROM clients c
        LEFT JOIN parties p ON p.id = (
            SELECT id FROM parties p2
            WHERE p2.client_id = c.id
            ORDER BY p2.date_partie DESC
            LIMIT 1
        )
        LEFT JOIN jeux     j ON p.jeu_id          = j.id
        LEFT JOIN dotations d ON p.dotation_gagnee = d.id
        $where
        ORDER BY c.date_inscription DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $clients = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'total'   => count($clients),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
