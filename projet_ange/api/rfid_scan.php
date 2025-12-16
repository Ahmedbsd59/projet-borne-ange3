<?php
// ================================================================
// api/rfid_scan.php
//
// POST { uid }         ← appelé par l'ESP8266 quand un badge est lu
//   → { success, known, prenom, client_id }
//
// GET (poll borne)     ← appelé par la borne toutes les secondes
//   → { found: false }   ou
//   → { found: true, uid, known, prenom, client_id }
// ================================================================
require_once 'db.php';
require_once 'logger.php';

header('Cache-Control: no-store, no-cache');

// ── ESP8266 dépose un scan ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $uid  = strtoupper(trim($data['uid'] ?? ''));

    if (!preg_match('/^[0-9A-F]{4,20}$/', $uid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'UID invalide']); exit;
    }

    $db = getDB();

    // Purge des scans non consommés depuis plus de 30 s (évite les fantômes)
    $db->exec("DELETE FROM rfid_scans WHERE scanne_le < DATE_SUB(NOW(), INTERVAL 30 SECOND)");

    // Insérer le nouveau scan
    $db->prepare("INSERT INTO rfid_scans (uid) VALUES (?)")->execute([$uid]);

    // Chercher si le badge est associé à un client
    $stmt = $db->prepare("SELECT id, prenom FROM clients WHERE rfid_uid = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$uid]);
    $client = $stmt->fetch();

    // Vérifier la limite journalière
    $limitAtteinte = false;
    if ($client) {
        $stmt2 = $db->prepare(
            "SELECT COUNT(*) FROM parties WHERE client_id = ? AND DATE(date_partie) = CURDATE()"
        );
        $stmt2->execute([$client['id']]);
        $limitAtteinte = $stmt2->fetchColumn() > 0;
    }

    logEvent('info', 'rfid_scan', ['uid' => $uid, 'known' => (bool)$client, 'limit' => $limitAtteinte]);

    echo json_encode([
        'success'        => true,
        'known'          => (bool)$client,
        'prenom'         => $client['prenom'] ?? null,
        'client_id'      => $client['id']     ?? null,
        'limit_atteinte' => $limitAtteinte,
    ]);
    exit;
}

// ── Borne web poll : y a-t-il un scan récent non consommé ? ────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = getDB();

    // Purge des scans trop anciens
    $db->exec("DELETE FROM rfid_scans WHERE scanne_le < DATE_SUB(NOW(), INTERVAL 30 SECOND)");

    // Récupérer le scan le plus récent non consommé
    $stmt = $db->query(
        "SELECT id, uid FROM rfid_scans WHERE consomme = 0 ORDER BY scanne_le DESC LIMIT 1"
    );
    $scan = $stmt->fetch();

    if (!$scan) {
        echo json_encode(['found' => false]); exit;
    }

    // Marquer comme consommé pour que la borne ne le lise qu'une fois
    $db->prepare("UPDATE rfid_scans SET consomme = 1 WHERE id = ?")->execute([$scan['id']]);

    // Chercher le client associé
    $stmt = $db->prepare("SELECT id, prenom FROM clients WHERE rfid_uid = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$scan['uid']]);
    $client = $stmt->fetch();

    // Vérifier la limite journalière
    $limitAtteinte = false;
    if ($client) {
        $stmt2 = $db->prepare(
            "SELECT COUNT(*) FROM parties WHERE client_id = ? AND DATE(date_partie) = CURDATE()"
        );
        $stmt2->execute([$client['id']]);
        $limitAtteinte = $stmt2->fetchColumn() > 0;
    }

    echo json_encode([
        'found'          => true,
        'uid'            => $scan['uid'],
        'known'          => (bool)$client,
        'prenom'         => $client['prenom'] ?? null,
        'client_id'      => $client['id']     ?? null,
        'limit_atteinte' => $limitAtteinte,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
