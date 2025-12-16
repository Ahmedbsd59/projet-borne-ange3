<?php
// ================================================================
// api/dotations_admin.php - CRUD dotations (admin seulement)
// GET    → liste toutes les dotations avec leur jeu
// POST   { action:'update', id, stock, probabilite, couleur }
// POST   { action:'create', jeu_id, libelle, valeur, probabilite, couleur, stock }
// POST   { action:'delete', id }
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';
require_once 'logger.php';

requireAdminAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("
        SELECT d.id, d.jeu_id, j.nom AS jeu, d.libelle, d.valeur,
               d.probabilite, d.couleur, d.stock
        FROM dotations d JOIN jeux j ON d.jeu_id = j.id
        ORDER BY j.id, d.probabilite DESC
    ");
    echo json_encode(['success' => true, 'dotations' => $stmt->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    if ($action === 'update') {
        $id    = (int)($data['id'] ?? 0);
        $stock = isset($data['stock'])       ? (int)$data['stock']          : null;
        $prob  = isset($data['probabilite']) ? (float)$data['probabilite']  : null;
        $coul  = isset($data['couleur'])     ? trim($data['couleur'])        : null;
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id manquant']); exit; }

        $sets = []; $params = [];
        if ($stock !== null) { $sets[] = 'stock = ?';       $params[] = $stock; }
        if ($prob  !== null) { $sets[] = 'probabilite = ?'; $params[] = $prob;  }
        if ($coul  !== null) { $sets[] = 'couleur = ?';     $params[] = $coul;  }
        if (!$sets)          { echo json_encode(['success'=>false,'message'=>'Rien à modifier']); exit; }
        $params[] = $id;
        $db->prepare("UPDATE dotations SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        logEvent('info', 'dotation_update', ['id' => $id, 'sets' => $sets]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'create') {
        $jeuId  = (int)($data['jeu_id']      ?? 0);
        $libel  = trim($data['libelle']       ?? '');
        $valeur = (float)($data['valeur']     ?? 0);
        $prob   = (float)($data['probabilite']?? 0);
        $coul   = trim($data['couleur']       ?? '#374151');
        $stock  = (int)($data['stock']        ?? 0);
        if (!$jeuId || !$libel) { echo json_encode(['success'=>false,'message'=>'jeu_id et libelle requis']); exit; }
        $db->prepare("INSERT INTO dotations (jeu_id, libelle, valeur, probabilite, couleur, stock) VALUES (?,?,?,?,?,?)")
           ->execute([$jeuId, $libel, $valeur, $prob, $coul, $stock]);
        logEvent('info', 'dotation_create', ['jeu_id' => $jeuId, 'libelle' => $libel]);
        echo json_encode(['success' => true, 'id' => (int)$db->lastInsertId()]);

    } elseif ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id manquant']); exit; }
        $db->prepare("DELETE FROM dotations WHERE id = ?")->execute([$id]);
        logEvent('info', 'dotation_delete', ['id' => $id]);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
