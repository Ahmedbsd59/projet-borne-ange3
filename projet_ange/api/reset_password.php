<?php
// ================================================================
// api/reset_password.php
// POST { action:'request', email }
//   → génère un token, retourne { success, token, prenom }
//     (en prod : envoyer par email ; ici on retourne le token pour démo)
// POST { action:'verify', token }
//   → { success, valid }
// POST { action:'reset', token, nouveau_mot_de_passe }
//   → { success, message }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]); exit;
}

// 3 demandes de reset par heure par IP
checkRateLimit('reset_password', 3, 3600, 3600);

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

$db = getDB();

// ── Action : demande de reset ──
if ($action === 'request') {
    $email = trim($data['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']); exit;
    }

    $stmt = $db->prepare("SELECT id, prenom FROM clients WHERE email = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    // Réponse générique même si le compte n'existe pas (anti-enumération)
    if (!$client) {
        echo json_encode(['success' => true, 'message' => 'Si ce compte existe, un lien a été envoyé.']); exit;
    }

    // Invalider les anciens tokens
    $db->prepare("UPDATE reset_tokens SET utilise=1 WHERE client_id=? AND utilise=0")->execute([$client['id']]);

    $token  = bin2hex(random_bytes(32));
    $expire = date('Y-m-d H:i:s', time() + 3600); // 1h
    $db->prepare("INSERT INTO reset_tokens (client_id, token, expire_a) VALUES (?,?,?)")
       ->execute([$client['id'], $token, $expire]);

    logEvent('info', 'reset_requested', ['client_id' => $client['id']]);

    // En production : envoyer par email. Ici on retourne le token pour la démo.
    echo json_encode([
        'success' => true,
        'message' => 'Lien de réinitialisation généré.',
        'token'   => $token,   // À supprimer en production (envoyer par email)
        'prenom'  => $client['prenom'],
    ]);
    exit;
}

// ── Action : vérifier le token ──
if ($action === 'verify') {
    $token = trim($data['token'] ?? '');
    $stmt  = $db->prepare("SELECT id FROM reset_tokens WHERE token=? AND utilise=0 AND expire_a > NOW() LIMIT 1");
    $stmt->execute([$token]);
    echo json_encode(['success' => true, 'valid' => (bool)$stmt->fetch()]);
    exit;
}

// ── Action : reset du mot de passe ──
if ($action === 'reset') {
    $token = trim($data['token'] ?? '');
    $mdp   = $data['nouveau_mot_de_passe'] ?? '';

    if (strlen($mdp) < 8) {
        echo json_encode(['success' => false, 'message' => 'Mot de passe trop court (8 caractères min)']); exit;
    }

    $stmt = $db->prepare("
        SELECT rt.id AS rt_id, rt.client_id
        FROM reset_tokens rt
        WHERE rt.token = ? AND rt.utilise = 0 AND rt.expire_a > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Lien invalide ou expiré']); exit;
    }

    $hash = password_hash($mdp, PASSWORD_BCRYPT);
    $db->prepare("UPDATE clients SET mot_de_passe=? WHERE id=?")->execute([$hash, $row['client_id']]);
    $db->prepare("UPDATE reset_tokens SET utilise=1 WHERE id=?")->execute([$row['rt_id']]);

    logEvent('info', 'reset_done', ['client_id' => $row['client_id']]);
    echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue']);
