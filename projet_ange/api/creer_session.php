<?php
// ================================================================
// api/creer_session.php - Ouvre une session de jeu sur la borne
// Appelé dès qu'un code barre valide est scanné
// Réponse : { success, session_id }
// ================================================================
require_once 'db.php';

$ip        = $_SERVER['REMOTE_ADDR'] ?? null;
$sessionId = bin2hex(random_bytes(16)); // identifiant 32 caractères hex

// Expiration automatique dans 30 minutes
$expiration = date('Y-m-d H:i:s', strtotime('+30 minutes'));

try {
    $db = getDB();
    $db->prepare("
        INSERT INTO sessions (id, ip_address, date_expiration, actif)
        VALUES (?, ?, ?, TRUE)
    ")->execute([$sessionId, $ip, $expiration]);

    echo json_encode(['success' => true, 'session_id' => $sessionId]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
