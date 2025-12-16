<?php
// ================================================================
// api/jeton_scan.php
//
// POST { valide: true }  ← appelé par l'ESP8266 quand un jeton
//                          rouge est détecté par le TCS3200
//
// GET (poll borne)       ← appelé par la borne toutes les secondes
//   → { found: false }
//   → { found: true }
// ================================================================
require_once 'db.php';
require_once 'logger.php';

header('Cache-Control: no-store, no-cache');
header('Content-Type: application/json');

// ── ESP8266 signale un jeton valide ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $valide = (bool)($data['valide'] ?? false);

    if (!$valide) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Jeton invalide']); exit;
    }

    $db = getDB();

    // Purge des jetons non consommés depuis plus de 30s
    $db->exec("DELETE FROM jeton_scans WHERE scanne_le < DATE_SUB(NOW(), INTERVAL 30 SECOND)");

    // Insérer le signal jeton
    $db->exec("INSERT INTO jeton_scans (valide) VALUES (1)");

    logEvent('info', 'jeton_scan', ['valide' => true]);

    echo json_encode(['success' => true]);
    exit;
}

// ── Borne poll : y a-t-il un jeton récent non consommé ? ───────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = getDB();

    // Purge des jetons trop anciens
    $db->exec("DELETE FROM jeton_scans WHERE scanne_le < DATE_SUB(NOW(), INTERVAL 30 SECOND)");

    $stmt = $db->query("SELECT id FROM jeton_scans WHERE consomme = 0 ORDER BY scanne_le DESC LIMIT 1");
    $jeton = $stmt->fetch();

    if (!$jeton) {
        echo json_encode(['found' => false]); exit;
    }

    // Marquer comme consommé
    $db->prepare("UPDATE jeton_scans SET consomme = 1 WHERE id = ?")->execute([$jeton['id']]);

    echo json_encode(['found' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
