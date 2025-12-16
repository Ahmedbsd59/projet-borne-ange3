<?php
// ================================================================
// api/verifier_deuxieme_chance.php
// POST JSON : { client_id, session_id }
// Vérifie si le client peut jouer sa 2ème chance :
//   - n'a pas déjà utilisé deuxieme_chance=1 dans cette session
//   - ou, sans session, pas plus d'une 2ème chance aujourd'hui
// Réponse : { autorisee: bool, message: string }
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['autorisee' => false, 'message' => 'POST uniquement']); exit;
}

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$clientId  = isset($data['client_id'])  ? (int)$data['client_id']  : null;
$sessionId = $data['session_id'] ?? null;
$partieId  = isset($data['partie_id'])  ? (int)$data['partie_id']  : null;

if (!$clientId) {
    echo json_encode(['autorisee' => false, 'message' => 'Client non identifié']); exit;
}

try {
    $db = getDB();

    // Vérifier que la partie de référence existe et était bien une défaite
    if ($partieId) {
        $stmt = $db->prepare("SELECT gagne, deuxieme_chance FROM parties WHERE id = ? AND client_id = ? LIMIT 1");
        $stmt->execute([$partieId, $clientId]);
        $partie = $stmt->fetch();

        if (!$partie) {
            echo json_encode(['autorisee' => false, 'message' => 'Partie introuvable']); exit;
        }
        if ($partie['gagne']) {
            echo json_encode(['autorisee' => false, 'message' => 'La 2ème chance est réservée aux défaites.']); exit;
        }
        if ($partie['deuxieme_chance']) {
            echo json_encode(['autorisee' => false, 'message' => 'Cette partie a déjà bénéficié d\'une 2ème chance.']); exit;
        }
    }

    // Vérifier par session si disponible
    if ($sessionId) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM parties
            WHERE client_id = ? AND session_id = ? AND deuxieme_chance = 1
        ");
        $stmt->execute([$clientId, $sessionId]);
    } else {
        // Fallback : une seule 2ème chance par client par jour
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM parties
            WHERE client_id = ? AND deuxieme_chance = 1 AND DATE(date_partie) = CURDATE()
        ");
        $stmt->execute([$clientId]);
    }

    $dejouee = (int)$stmt->fetchColumn() > 0;

    if ($dejouee) {
        echo json_encode([
            'autorisee' => false,
            'message'   => 'Vous avez déjà utilisé votre 2ème chance pour cette session.',
        ]); exit;
    }

    // Rattacher les parties de cette session au client (jouées sans compte)
    // Cela permet à l'application mobile de détecter la 2ème chance disponible
    if ($sessionId) {
        $db->prepare("
            UPDATE parties SET client_id = ?
            WHERE session_id = ? AND client_id IS NULL
        ")->execute([$clientId, $sessionId]);
    }

    echo json_encode(['autorisee' => true, 'message' => 'OK']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['autorisee' => false, 'message' => 'Erreur serveur']);
}
