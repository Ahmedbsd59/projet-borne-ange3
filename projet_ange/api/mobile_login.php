<?php
// ================================================================
// api/mobile_login.php — Authentification client (application mobile)
// POST { email, password } → { success, token, client }
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST uniquement']);
    exit;
}

define('MOBILE_SECRET', 'borneplay_mobile_2026_bts_ciel');

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($body['email']    ?? '');
$password =      $body['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT id, nom, prenom, email, mot_de_passe, rfid_uid, actif,
                DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription
         FROM clients WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if (!$client || !(int)$client['actif']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Compte introuvable ou désactivé']);
        exit;
    }

    if (!password_verify($password, $client['mot_de_passe'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
        exit;
    }

    // Token stateless : base64(id:email:hmac)
    $payload = $client['id'] . ':' . $client['email'];
    $hmac    = hash_hmac('sha256', $payload, MOBILE_SECRET);
    $token   = base64_encode($payload . ':' . $hmac);

    echo json_encode([
        'success' => true,
        'token'   => $token,
        'client'  => [
            'id'                => (int)$client['id'],
            'prenom'            => $client['prenom'],
            'nom'               => $client['nom'],
            'email'             => $client['email'],
            'rfid_uid'          => $client['rfid_uid'],
            'date_inscription'  => $client['date_inscription'],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
