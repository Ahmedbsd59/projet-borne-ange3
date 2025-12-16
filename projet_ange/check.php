<?php
// Appelé par l'ESP8266 : GET /check.php?uid=XXXX
// Répond "AUTORISE" si le badge est connu et actif, sinon "REFUSE"
require_once 'api/db.php';

$uid = strtoupper(trim($_GET['uid'] ?? ''));

if (!preg_match('/^[0-9A-F]{4,20}$/', $uid)) {
    http_response_code(400);
    echo "REFUSE";
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id FROM clients WHERE rfid_uid = ? AND actif = 1 LIMIT 1");
$stmt->execute([$uid]);

echo $stmt->fetch() ? "AUTORISE" : "REFUSE";
