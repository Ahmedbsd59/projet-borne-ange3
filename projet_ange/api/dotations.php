<?php
// ================================================================
// api/dotations.php - Segments de jeu (roue, jackpot…) avec prob/couleur
// GET ?jeu_id=1
// Réponse : { success, segments: [{label, prob, couleur, stock}] }
// Public : appelé par la borne sans token admin
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']); exit;
}

// Cache 60 s côté client, 30 s pour les proxies — les dotations changent rarement
header('Cache-Control: public, max-age=60, s-maxage=30');
header('Vary: Accept-Encoding');

$jeuId = (int)($_GET['jeu_id'] ?? 1);
if ($jeuId < 1) {
    echo json_encode(['success' => false, 'message' => 'jeu_id invalide']); exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT libelle AS label, probabilite AS prob, couleur, stock
        FROM dotations
        WHERE jeu_id = ?
        ORDER BY probabilite DESC
    ");
    $stmt->execute([$jeuId]);
    $rows = $stmt->fetchAll();

    // Normalise probabilite en float
    foreach ($rows as &$r) {
        $r['prob']  = (float)$r['prob'];
        $r['stock'] = (int)$r['stock'];
    }

    echo json_encode(['success' => true, 'segments' => $rows]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD']);
}
