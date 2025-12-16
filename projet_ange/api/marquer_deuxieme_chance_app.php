<?php
// ================================================================
// api/marquer_deuxieme_chance_app.php
// POST JSON : { client_id }
// Marque que le client a choisi de jouer sa 2ème chance depuis l'app.
// Positionne dc_app_date = CURDATE() sur la ligne clients.
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST uniquement']); exit;
}

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$clientId = isset($data['client_id']) ? (int)$data['client_id'] : 0;

if (!$clientId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'client_id manquant']); exit;
}

try {
    $db = getDB();
    $db->prepare("UPDATE clients SET dc_app_date = CURDATE() WHERE id = ?")
       ->execute([$clientId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
