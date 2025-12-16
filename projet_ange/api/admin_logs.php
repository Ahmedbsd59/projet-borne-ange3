<?php
// ================================================================
// api/admin_logs.php - Dernières connexions/actions admin
// GET ?limit=50 → { success, logs: [{ts, level, action, ip, ctx}] }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

requireAdminAuth();

$limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));
$filter = $_GET['filter'] ?? ''; // 'login' | 'logout' | 'all'

define('LOG_FILE', '/var/log/borne/app.log');

if (!file_exists(LOG_FILE)) {
    echo json_encode(['success' => true, 'logs' => []]);
    exit;
}

// Read last N relevant lines efficiently
$lines = [];
$fh = fopen(LOG_FILE, 'r');
if (!$fh) {
    echo json_encode(['success' => true, 'logs' => []]);
    exit;
}

// Collect matching lines (admin events)
$adminActions = ['admin_login_ok', 'admin_login_echec', 'admin_logout', 'maintenance_on', 'maintenance_off', 'reset_requested', 'reset_done'];
$buffer = [];
while (!feof($fh)) {
    $line = fgets($fh);
    if (!$line) continue;
    $entry = json_decode(trim($line), true);
    if (!$entry) continue;
    if ($filter === 'login' && !in_array($entry['action'] ?? '', ['admin_login_ok', 'admin_login_echec', 'admin_logout'], true)) continue;
    if (!in_array($entry['action'] ?? '', $adminActions, true)) continue;
    $buffer[] = $entry;
}
fclose($fh);

// Most recent first
$buffer = array_reverse($buffer);
$logs   = array_slice($buffer, 0, $limit);

echo json_encode(['success' => true, 'logs' => $logs, 'total' => count($buffer)]);
