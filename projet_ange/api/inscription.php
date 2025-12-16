<?php
// ================================================================
// api/inscription.php - Création d'un compte client
// Champs : nom, prenom, email, mot_de_passe
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// 3 inscriptions par heure par IP → blocage 1h
checkRateLimit('inscription', 3, 3600, 3600);

$data = json_decode(file_get_contents('php://input'), true);

$prenom       = trim($data['prenom']       ?? '');
$nom          = trim($data['nom']          ?? '');
$email        = trim($data['email']        ?? '');
$mot_de_passe = trim($data['mot_de_passe'] ?? '');

// --- Validation ---
if (strlen($prenom) < 2) {
    echo json_encode(['success' => false, 'message' => 'Prénom requis (minimum 2 caractères)']); exit;
}
if (strlen($nom) < 2) {
    echo json_encode(['success' => false, 'message' => 'Nom requis (minimum 2 caractères)']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Adresse e-mail invalide']); exit;
}
if (strlen($mot_de_passe) < 8) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']); exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette adresse e-mail est déjà utilisée']); exit;
    }

    $stmt = $db->prepare("
        INSERT INTO clients (nom, prenom, email, mot_de_passe)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $nom,
        $prenom,
        $email,
        password_hash($mot_de_passe, PASSWORD_BCRYPT)
    ]);
    $clientId = $db->lastInsertId();

    echo json_encode([
        'success'   => true,
        'message'   => 'Compte créé avec succès !',
        'client_id' => $clientId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
