<?php
// ================================================================
// api/admin_login.php - Authentification administrateur
// POST JSON : { login, password }
// Réponse   : { success, role, message }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST uniquement']);
    exit;
}

// 5 tentatives par 10 min → blocage 30 min
checkRateLimit('admin_login', 5, 600, 1800);

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$login    = trim($data['login']    ?? '');
$password = trim($data['password'] ?? '');

if ($login === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Identifiant et mot de passe requis']);
    exit;
}

// Mots de passe par défaut (utilisés si la BDD contient encore le hash placeholder)
$defaults = [
    'admin'       => 'admin123',
    'maintenance' => 'maint2025',
];

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, mot_de_passe, role FROM administrateurs WHERE login = ? LIMIT 1");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    $hash  = $admin['mot_de_passe'];
    $valid = false;

    // Vérification normale avec bcrypt
    if (password_verify($password, $hash)) {
        $valid = true;
    }
    // Fallback : hash placeholder → vérifier contre les credentials par défaut
    elseif (
        str_starts_with($hash, '$2y$10$exampleHash') &&
        isset($defaults[$login]) &&
        $password === $defaults[$login]
    ) {
        // Mettre à jour le hash en BDD maintenant qu'on a le bon mot de passe
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("UPDATE administrateurs SET mot_de_passe = ? WHERE id = ?")
           ->execute([$newHash, $admin['id']]);
        $valid = true;
    }

    if (!$valid) {
        logEvent('warn', 'admin_login_echec', ['login' => $login]);
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    clearRateLimit('admin_login');

    // Générer un token de session admin (valable 8h)
    $token = createAdminToken((int)$admin['id'], $admin['role'], 480);

    // CSRF token dérivé du Bearer token
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    $csrfToken = getCsrfToken();

    logEvent('info', 'admin_login_ok', ['login' => $login, 'role' => $admin['role']]);

    echo json_encode([
        'success'    => true,
        'role'       => $admin['role'],
        'login'      => $login,
        'token'      => $token,
        'csrf_token' => $csrfToken,
        'message'    => 'Connexion réussie',
    ]);

} catch (PDOException $e) {
    logEvent('error', 'admin_login_bdd', ['msg' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
