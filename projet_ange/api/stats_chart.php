<?php
// ================================================================
// api/stats_chart.php - Données pour les graphiques du dashboard
// GET ?periode=week|month
// Réponse : { success, labels, parties, gains, jeux_repartition, stock_alertes }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']); exit;
}

requireAdminAuth();

// Cache 5 min — les stats graphiques n'ont pas besoin d'être temps-réel
header('Cache-Control: private, max-age=300');
header('Vary: Authorization');

$periode = ($_GET['periode'] ?? 'week') === 'month' ? 'month' : 'week';

try {
    $db = getDB();

    // ── Série temporelle (parties + gains par jour) ──
    if ($periode === 'week') {
        $sql = "
            SELECT
                DATE_FORMAT(MIN(date_partie), '%a %d') AS label,
                COUNT(*) AS nb_parties,
                SUM(gagne) AS nb_gains
            FROM parties
            WHERE date_partie >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
            GROUP BY DATE(date_partie)
            ORDER BY DATE(date_partie)
        ";
    } else {
        $sql = "
            SELECT
                CONCAT('S', WEEK(date_partie)) AS label,
                COUNT(*) AS nb_parties,
                SUM(gagne) AS nb_gains
            FROM parties
            WHERE date_partie >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
            GROUP BY WEEK(date_partie)
            ORDER BY WEEK(date_partie)
        ";
    }
    $stmt = $db->query($sql);
    $serie = $stmt->fetchAll();

    // ── Répartition par jeu ──
    $stmt = $db->query("
        SELECT j.nom AS jeu, COUNT(*) AS nb, SUM(p.gagne) AS nb_gains
        FROM parties p JOIN jeux j ON p.jeu_id = j.id
        GROUP BY j.id, j.nom
        ORDER BY nb DESC
    ");
    $jeuxRep = $stmt->fetchAll();

    // ── Heures de pointe (parties par heure, aujourd'hui/semaine) ──
    $stmt = $db->query("
        SELECT HOUR(date_partie) AS heure, COUNT(*) AS nb
        FROM parties
        WHERE date_partie >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY HOUR(date_partie)
        ORDER BY heure
    ");
    $heures = $stmt->fetchAll();

    // ── Alertes stock faible (stock > 0 ET stock < 10, ou stock = 0) ──
    $stmt = $db->query("
        SELECT d.id, d.libelle, d.stock, j.nom AS jeu
        FROM dotations d JOIN jeux j ON d.jeu_id = j.id
        WHERE d.stock >= 0 AND d.stock <= 10
        ORDER BY d.stock ASC
    ");
    $alertes = $stmt->fetchAll();

    echo json_encode([
        'success'        => true,
        'serie'          => $serie,
        'jeux_rep'       => $jeuxRep,
        'heures_pointe'  => $heures,
        'stock_alertes'  => $alertes,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD']);
}
