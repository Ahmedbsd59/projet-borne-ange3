<?php
// ================================================================
// api/verifier_client.php - Vérifie email + mot de passe d'un client
// POST JSON : { email, mot_de_passe (optionnel) }
// Réponse   : { found: bool, prenom, message }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['found' => false]); exit;
}

// 5 tentatives par tranche de 5 min → blocage 15 min
checkRateLimit('login_client', 5, 300, 900);

$data         = json_decode(file_get_contents('php://input'), true) ?? [];
$email        = trim($data['email']        ?? '');
$mot_de_passe = trim($data['mot_de_passe'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['found' => false, 'message' => 'Email invalide']); exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, prenom, mot_de_passe FROM clients WHERE email = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$email]);
    $row  = $stmt->fetch();

    if (!$row) {
        echo json_encode(['found' => false, 'message' => 'Aucun compte trouvé pour cet email']);
        exit;
    }

    if ($mot_de_passe === '') {
        echo json_encode(['found' => false, 'message' => 'Mot de passe requis']);
        exit;
    }

    if (!password_verify($mot_de_passe, $row['mot_de_passe'])) {
        logEvent('warn', 'login_echec', ['email' => $email]);
        echo json_encode(['found' => false, 'message' => 'Mot de passe incorrect']);
        exit;
    }

    clearRateLimit('login_client');
    logEvent('info', 'login_ok', ['email' => $email, 'client_id' => $row['id']]);
    echo json_encode(['found' => true, 'prenom' => $row['prenom'], 'client_id' => $row['id']]);

} catch (PDOException $e) {
    logEvent('error', 'login_erreur_bdd', ['msg' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['found' => false, 'message' => 'Erreur serveur']);
}
