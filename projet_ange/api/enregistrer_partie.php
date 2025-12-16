<?php
// ================================================================
// api/enregistrer_partie.php - Enregistrement d'une partie jouée
// Accepte POST JSON :
//   Format dashboard : { email, methode, jeu, dotation, gagne }
//   Format jeux      : { jeu, gain, gagne, score, code_barre }
// ================================================================
require_once 'db.php';
require_once 'logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST uniquement']); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Champs communs ──
$jeuxMap = ['roue_chance' => 1, 'memory' => 2, 'quiz' => 3, 'jackpot' => 4,
            'Roue'        => 1, 'Memory' => 2, 'Quiz' => 3, 'Jackpot' => 4];

// Normalisation du nom de jeu
$jeuRaw  = $data['jeu'] ?? '';
$jeuId   = $jeuxMap[$jeuRaw] ?? null;

// Résultat / dotation
$gain            = $data['gain']           ?? $data['dotation'] ?? null;
$gagne           = (bool)($data['gagne']   ?? false);
$score           = (int)($data['score']    ?? 0);
$code_barre      = $data['code_barre']     ?? null;
$deuxieme_chance = (bool)($data['deuxieme_chance'] ?? false);
$duree_partie    = isset($data['duree_partie']) ? (int)$data['duree_partie'] : null;
$gain_libelle    = $gain && strtolower($gain) !== 'perdu' ? $gain : null;
$session_id      = !empty($data['session_id']) ? $data['session_id'] : null;
$partie_id_parent = isset($data['partie_id']) && $data['partie_id'] ? (int)$data['partie_id'] : null;

// Méthode de connexion (info stockée dans le feed du dashboard, pas en BDD)
$methode    = $data['methode'] ?? null;

// Priorité 1 : client_id envoyé directement dans le body
$clientId = isset($data['client_id']) && $data['client_id'] ? (int)$data['client_id'] : null;

// Priorité 2 : Email → retrouver le client_id
if ($clientId === null && !empty($data['email'])) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
        $stmt->execute([$data['email']]);
        $row  = $stmt->fetch();
        if ($row) $clientId = $row['id'];
    } catch (PDOException $e) {}
}

// Priorité 3 : Session PHP (jeux natifs standalone)
if ($clientId === null) {
    @session_start();
    $clientId = $_SESSION['client_id'] ?? null;
}

if (!$jeuId) {
    echo json_encode(['success' => false, 'message' => 'Jeu inconnu : ' . $jeuRaw]); exit;
}

try {
    $db = getDB();

    // Trouver la dotation correspondante
    $dotationId = null;
    if ($gain && strtolower($gain) !== 'perdu') {
        $stmt = $db->prepare(
            "SELECT id FROM dotations WHERE jeu_id = ? AND libelle LIKE ? LIMIT 1"
        );
        $stmt->execute([$jeuId, '%' . $gain . '%']);
        $dot = $stmt->fetch();
        if ($dot) $dotationId = $dot['id'];
    }

    // UPDATE ou INSERT selon qu'on a un partie_id parent (2ème chance liée)
    if ($partie_id_parent && $deuxieme_chance) {
        // Vérifier que la partie parente était bien une défaite et n'a pas déjà eu une 2ème chance
        $check = $db->prepare("SELECT gagne, deuxieme_chance FROM parties WHERE id = ? LIMIT 1");
        $check->execute([$partie_id_parent]);
        $parent = $check->fetch();

        if (!$parent || $parent['gagne'] || $parent['deuxieme_chance']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'La 2ème chance est réservée aux défaites non déjà rejouées.']); exit;
        }

        $stmt = $db->prepare("
            UPDATE parties SET
                deuxieme_chance = 1,
                dc_jeu_id       = ?,
                dc_gain_libelle = ?,
                dc_gagne        = ?,
                dc_score        = ?,
                dc_date         = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$jeuId, $gain_libelle, $gagne ? 1 : 0, $score, $partie_id_parent]);
        $partieId = $partie_id_parent;
    } else {
        // Insérer la partie
        $stmt = $db->prepare("
            INSERT INTO parties
                (client_id, jeu_id, dotation_gagnee, code_barre_scan, score, gagne,
                 deuxieme_chance, duree_partie, gain_libelle, session_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId, $jeuId, $dotationId, $code_barre, $score, $gagne ? 1 : 0,
            $deuxieme_chance ? 1 : 0, $duree_partie, $gain_libelle, $session_id
        ]);
        $partieId = $db->lastInsertId();
    }

    // Mettre à jour la session avec le client_id si connu
    if ($session_id && $clientId) {
        $db->prepare("
            UPDATE sessions SET client_id = ? WHERE id = ? AND client_id IS NULL
        ")->execute([$clientId, $session_id]);
    }

    // Supprimer le code barre utilisé et en générer un nouveau
    if ($code_barre) {
        $db->prepare("DELETE FROM codes_barres WHERE code = ?")->execute([$code_barre]);
        $nouveau_code = (string)random_int(1000000000000, 9999999999999);
        $db->prepare("INSERT INTO codes_barres (code, utilise) VALUES (?, FALSE)")->execute([$nouveau_code]);
    }

    // Décrémenter le stock de la dotation
    if ($dotationId) {
        $db->prepare("
            UPDATE dotations SET stock = stock - 1
            WHERE id = ? AND stock > 0
        ")->execute([$dotationId]);
    }

    logEvent('info', 'partie_enregistree', [
        'partie_id'  => (int)$partieId,
        'jeu'        => $jeuRaw,
        'gagne'      => $gagne,
        'gain'       => $gain_libelle,
        'client_id'  => $clientId,
        'session_id' => $session_id,
    ]);

    echo json_encode([
        'success'   => true,
        'message'   => 'Partie enregistrée',
        'partie_id' => (int)$partieId,
    ]);

} catch (PDOException $e) {
    logEvent('error', 'partie_erreur_bdd', ['msg' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
