<?php
// ================================================================
// api/parties.php - Historique des parties pour le dashboard admin
// GET params : limit, order, jeu_id, gagne (0|1), date_debut, date_fin, search
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

requireAdminAuth();

$limit      = max(1, min(100, (int)($_GET['limit'] ?? 25)));
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $limit;
$order      = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$jeuId      = isset($_GET['jeu_id'])     && $_GET['jeu_id'] !== ''  ? (int)$_GET['jeu_id']     : null;
$gagne      = isset($_GET['gagne'])      && $_GET['gagne']  !== ''  ? (int)$_GET['gagne']       : null;
$dateDebut  = !empty($_GET['date_debut']) ? $_GET['date_debut'] : null;
$dateFin    = !empty($_GET['date_fin'])   ? $_GET['date_fin']   : null;
$search     = !empty($_GET['search'])     ? trim($_GET['search']) : null;

try {
    $db = getDB();

    $where  = [];
    $params = [];

    if ($jeuId !== null)    { $where[] = 'p.jeu_id = ?';              $params[] = $jeuId; }
    if ($gagne !== null)    { $where[] = 'p.gagne = ?';               $params[] = $gagne; }
    if ($dateDebut !== null){ $where[] = 'DATE(p.date_partie) >= ?';  $params[] = $dateDebut; }
    if ($dateFin !== null)  { $where[] = 'DATE(p.date_partie) <= ?';  $params[] = $dateFin; }
    if ($search !== null)   {
        $where[]  = '(c.prenom LIKE ? OR c.nom LIKE ? OR c.email LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            p.id,
            DATE_FORMAT(p.date_partie, '%H:%i')    AS heure,
            DATE_FORMAT(p.date_partie, '%d/%m/%Y') AS date_partie,
            COALESCE(CONCAT(c.prenom, ' ', c.nom), 'Anonyme') AS client,
            COALESCE(c.email, '')                              AS email,
            j.nom                                             AS jeu,
            CASE
                WHEN p.code_barre_scan IS NOT NULL THEN 'QR Code'
                WHEN c.id IS NOT NULL              THEN 'RFID'
                ELSE 'Jeton'
            END AS methode,
            COALESCE(p.gain_libelle, COALESCE(d.libelle, 'Perdu')) AS dotation,
            p.gagne,
            p.score,
            p.deuxieme_chance,
            p.duree_partie
        FROM parties p
        LEFT JOIN clients   c ON p.client_id      = c.id
        LEFT JOIN jeux      j ON p.jeu_id          = j.id
        LEFT JOIN dotations d ON p.dotation_gagnee = d.id
        $whereClause
        ORDER BY p.date_partie $order
        LIMIT ? OFFSET ?
    ";
    $countParams = $params; // pour le COUNT (sans LIMIT/OFFSET)
    $params[] = $limit;
    $params[] = $offset;

    // Total pour la pagination
    $countSql  = "SELECT COUNT(*) FROM parties p LEFT JOIN clients c ON p.client_id = c.id LEFT JOIN jeux j ON p.jeu_id = j.id $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success'    => true,
        'parties'    => $rows,
        'total'      => $total,
        'page'       => $page,
        'limit'      => $limit,
        'pages'      => (int)ceil($total / $limit),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
