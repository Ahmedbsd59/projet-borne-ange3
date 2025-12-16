<?php
// ================================================================
// api/rate_limit.php - Rate limiting + Auth admin + CSRF
// Doit être inclus APRÈS db.php (utilise getDB())
// ================================================================

/**
 * Génère et retourne un token CSRF lié au token admin Bearer.
 * Le dashboard doit inclure ce token dans le header X-CSRF-Token
 * pour toutes les requêtes qui modifient des données.
 */
function getCsrfToken(): string {
    $bearer = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    // CSRF token = HMAC du Bearer avec une clé secrète fixe (propre à l'instance)
    $secret = getenv('CSRF_SECRET') ?: 'borne_csrf_secret_2026';
    return hash_hmac('sha256', $bearer, $secret);
}

/**
 * Vérifie le header X-CSRF-Token pour les requêtes mutantes (POST/PUT/DELETE).
 * À appeler dans les endpoints admin qui modifient des données.
 */
function requireCsrf(): void {
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['POST','PUT','DELETE','PATCH'], true)) return;
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(getCsrfToken(), $provided)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
}

/**
 * Vérifie et incrémente le compteur de tentatives pour une IP+endpoint.
 * Bloque la requête avec HTTP 429 si le seuil est dépassé.
 *
 * @param string $endpoint      Nom logique de l'endpoint (ex: 'login')
 * @param int    $maxTentatives Nombre de tentatives avant blocage
 * @param int    $fenetreSec    Fenêtre glissante en secondes
 * @param int    $blocSec       Durée du blocage en secondes
 */
function checkRateLimit(string $endpoint, int $maxTentatives = 5, int $fenetreSec = 300, int $blocSec = 900): void {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Prendre seulement la première IP si liste (proxy)
    $ip = trim(explode(',', $ip)[0]);

    $db = getDB();

    // Purge des entrées expirées
    $db->prepare("DELETE FROM rate_limits WHERE derniere_tentative < DATE_SUB(NOW(), INTERVAL ? SECOND)")
       ->execute([$fenetreSec * 2]);

    // Récupérer l'état actuel
    $stmt = $db->prepare("SELECT tentatives, bloque_jusqu_a FROM rate_limits WHERE ip = ? AND endpoint = ? LIMIT 1");
    $stmt->execute([$ip, $endpoint]);
    $row = $stmt->fetch();

    // Vérifier si actuellement bloqué
    if ($row && $row['bloque_jusqu_a'] && strtotime($row['bloque_jusqu_a']) > time()) {
        $reste = (int)ceil((strtotime($row['bloque_jusqu_a']) - time()) / 60);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'found'   => false,
            'message' => "Trop de tentatives. Réessayez dans {$reste} minute(s).",
        ]);
        exit;
    }

    if ($row) {
        $nouvelles = (int)$row['tentatives'] + 1;
        $bloqueJusqua = $nouvelles >= $maxTentatives
            ? date('Y-m-d H:i:s', time() + $blocSec)
            : null;

        $db->prepare("
            UPDATE rate_limits
            SET tentatives = ?, derniere_tentative = NOW(), bloque_jusqu_a = ?
            WHERE ip = ? AND endpoint = ?
        ")->execute([$nouvelles, $bloqueJusqua, $ip, $endpoint]);
    } else {
        $db->prepare("INSERT INTO rate_limits (ip, endpoint, tentatives) VALUES (?, ?, 1)")
           ->execute([$ip, $endpoint]);
    }
}

/**
 * Réinitialise le compteur après un succès (ex: login réussi).
 */
function clearRateLimit(string $endpoint): void {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = trim(explode(',', $ip)[0]);
    getDB()->prepare("DELETE FROM rate_limits WHERE ip = ? AND endpoint = ?")
           ->execute([$ip, $endpoint]);
}

/**
 * Génère un token admin et le persiste en BDD.
 */
function createAdminToken(int $adminId, string $role, int $dureeMinutes = 480): string {
    $token = bin2hex(random_bytes(32));
    $expire = date('Y-m-d H:i:s', time() + $dureeMinutes * 60);
    getDB()->prepare("INSERT INTO admin_tokens (token, admin_id, role, expire_a) VALUES (?, ?, ?, ?)")
           ->execute([$token, $adminId, $role, $expire]);
    return $token;
}

/**
 * Valide un token admin. Retourne ['admin_id', 'role'] ou null si invalide.
 */
function validateAdminToken(string $token): ?array {
    $stmt = getDB()->prepare(
        "SELECT admin_id, role FROM admin_tokens WHERE token = ? AND expire_a > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Supprime les tokens expirés et les entrées rate_limits périmées.
 * Appelé occasionnellement depuis requireAdminAuth (1 chance sur 50).
 */
function purgeExpired(): void {
    $db = getDB();
    $db->exec("DELETE FROM admin_tokens WHERE expire_a < NOW()");
    $db->exec("DELETE FROM rate_limits WHERE derniere_tentative < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
}

/**
 * Révoque un token admin (déconnexion).
 */
function revokeAdminToken(string $token): void {
    getDB()->prepare("DELETE FROM admin_tokens WHERE token = ?")->execute([$token]);
}

/**
 * Vérifie le token admin depuis le header Authorization: Bearer <token>.
 * Bloque avec 401 si absent ou invalide.
 */
function requireAdminAuth(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token  = '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        $token = trim($m[1]);
    }
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    $admin = validateAdminToken($token);
    if (!$admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expirée ou invalide']);
        exit;
    }
    // Purge probabiliste (2% des requêtes) pour éviter la croissance infinie des tables
    if (mt_rand(1, 50) === 1) purgeExpired();
    return $admin;
}
