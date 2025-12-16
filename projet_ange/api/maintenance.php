<?php
// ================================================================
// api/maintenance.php
// GET  (public) → { actif: bool, message }
// POST (admin)  { actif: bool, message? } → active/désactive
// Stocke l'état dans un fichier JSON (pas besoin de DB)
// ================================================================
require_once 'db.php';

define('MAINTENANCE_FILE', '/tmp/borne_maintenance.json');

function getMaintenance(): array {
    if (!file_exists(MAINTENANCE_FILE)) return ['actif' => false, 'message' => 'Borne en maintenance.'];
    return json_decode(file_get_contents(MAINTENANCE_FILE), true) ?? ['actif' => false, 'message' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-cache');
    echo json_encode(['success' => true] + getMaintenance());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'rate_limit.php';
    require_once 'logger.php';
    requireAdminAuth();
    requireCsrf();

    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $actif   = (bool)($data['actif']   ?? false);
    $message = trim($data['message']   ?? 'Borne temporairement indisponible. Revenez bientôt !');

    $state = ['actif' => $actif, 'message' => $message];
    file_put_contents(MAINTENANCE_FILE, json_encode($state));
    logEvent('info', 'maintenance_' . ($actif ? 'on' : 'off'), ['message' => $message]);
    echo json_encode(['success' => true] + $state);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false]);
