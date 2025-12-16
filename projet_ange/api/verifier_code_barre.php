<?php
// ================================================================
// api/verifier_code_barre.php - Vérification d'un code barre ticket
// Le code est supprimé de la table dès qu'il est validé.
// ================================================================
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$code = trim($data['code'] ?? '');

if (!preg_match('/^\d{8,14}$/', $code)) {
    echo json_encode(['valid' => false, 'message' => 'Format de code invalide']); exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT id, utilise FROM codes_barres WHERE code = ?");
    $stmt->execute([$code]);
    $cb = $stmt->fetch();

    if (!$cb) {
        // Code inconnu → refuser
        echo json_encode(['valid' => false, 'message' => 'Ce ticket n\'est pas reconnu.']);
        exit;
    }

    if ($cb['utilise']) {
        // Code marqué utilisé → supprimer et refuser
        $db->prepare("DELETE FROM codes_barres WHERE code = ?")->execute([$code]);
        echo json_encode(['valid' => true, 'used' => true, 'message' => 'Ce ticket a déjà été utilisé.']);
        exit;
    }

    // Code connu et disponible → supprimer immédiatement
    $db->prepare("DELETE FROM codes_barres WHERE code = ?")->execute([$code]);

    echo json_encode([
        'valid'   => true,
        'used'    => false,
        'jeux'    => ['roue_chance', 'memory', 'jackpot'],
        'message' => 'Ticket valide !'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Erreur serveur']);
}
