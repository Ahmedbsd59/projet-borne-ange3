<?php
// ================================================================
// api/admin_logout.php - Révocation du token admin
// POST (pas de body nécessaire — token lu depuis Authorization header)
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]); exit;
}

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
    revokeAdminToken(trim($m[1]));
    logEvent('info', 'admin_logout', []);
}

echo json_encode(['success' => true]);
