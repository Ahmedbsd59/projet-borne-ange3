<?php
// ================================================================
// api/associer_rfid.php - Associe un badge RFID à un client (admin)
// POST { action:'associer', client_id, rfid_uid }
//   → { success, message }
// POST { action:'dissocier', client_id }
//   → { success, message }
// GET  ?client_id=X
//   → { success, rfid_uid }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

requireAdminAuth();

$db = getDB();

// ── GET : récupérer le badge d'un client ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $clientId = (int)($_GET['client_id'] ?? 0);
    if ($clientId <= 0) { http_response_code(400); echo json_encode(['success' => false]); exit; }
    $stmt = $db->prepare("SELECT rfid_uid FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch();
    echo json_encode(['success' => true, 'rfid_uid' => $row['rfid_uid'] ?? null]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false]); exit;
}

requireCsrf();

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$action   = $data['action'] ?? '';
$clientId = (int)($data['client_id'] ?? 0);

if ($clientId <= 0) {
    echo json_encode(['success' => false, 'message' => 'client_id invalide']); exit;
}

// ── Associer ──
if ($action === 'associer') {
    $uid = strtoupper(trim($data['rfid_uid'] ?? ''));
    if (!preg_match('/^[0-9A-F]{4,20}$/', $uid)) {
        echo json_encode(['success' => false, 'message' => 'UID invalide (format hex 4-20 chars)']); exit;
    }

    // Vérifier que cet UID n'est pas déjà pris par un autre client
    $stmt = $db->prepare("SELECT id FROM clients WHERE rfid_uid = ? AND id != ? LIMIT 1");
    $stmt->execute([$uid, $clientId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce badge est déjà associé à un autre client']); exit;
    }

    $db->prepare("UPDATE clients SET rfid_uid = ? WHERE id = ?")->execute([$uid, $clientId]);
    logEvent('info', 'rfid_associe', ['client_id' => $clientId, 'uid' => $uid]);
    echo json_encode(['success' => true, 'message' => 'Badge associé avec succès']);
    exit;
}

// ── Dissocier ──
if ($action === 'dissocier') {
    $db->prepare("UPDATE clients SET rfid_uid = NULL WHERE id = ?")->execute([$clientId]);
    logEvent('info', 'rfid_dissocie', ['client_id' => $clientId]);
    echo json_encode(['success' => true, 'message' => 'Badge dissocié']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue']);
