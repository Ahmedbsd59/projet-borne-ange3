<?php
// ================================================================
// api/fermer_session.php - Ferme une session de jeu
// Appelé quand le joueur retourne à l'accueil
// POST JSON : { session_id }
// ================================================================
require_once 'db.php';

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = trim($data['session_id'] ?? '');

if (!$sessionId) {
    echo json_encode(['success' => false]); exit;
}

try {
    $db = getDB();
    $db->prepare("
        UPDATE sessions
        SET actif = FALSE, date_expiration = NOW()
        WHERE id = ?
    ")->execute([$sessionId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
