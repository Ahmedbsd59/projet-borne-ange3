<?php
// ================================================================
// api/mobile_profil.php — Profil + stats + parties du client connecté
// GET  Authorization: Bearer <token>
// → { success, client, stats, parties }
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

define('MOBILE_SECRET', 'borneplay_mobile_2026_bts_ciel');

function verifyMobileToken(string $token): ?int {
    $decoded = base64_decode($token, true);
    if (!$decoded) return null;

    // Format attendu : id:email:hmac
    $lastColon = strrpos($decoded, ':');
    if ($lastColon === false) return null;

    $hmac    = substr($decoded, $lastColon + 1);
    $payload = substr($decoded, 0, $lastColon);

    $expected = hash_hmac('sha256', $payload, MOBILE_SECRET);
    if (!hash_equals($expected, $hmac)) return null;

    $firstColon = strpos($payload, ':');
    if ($firstColon === false) return null;

    $id = (int)substr($payload, 0, $firstColon);
    return $id > 0 ? $id : null;
}

$auth     = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token    = str_replace('Bearer ', '', $auth);
$clientId = verifyMobileToken($token);

if (!$clientId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
    exit;
}

try {
    $db = getDB();

    // Infos client
    $stmt = $db->prepare(
        "SELECT id, prenom, nom, email, rfid_uid, dc_app_date,
                DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription
         FROM clients WHERE id = ? AND actif = 1 LIMIT 1"
    );
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    if (!$client) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Client introuvable']);
        exit;
    }

    // Stats globales
    $s = $db->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(gagne), 0) AS wins FROM parties WHERE client_id = ?"
    );
    $s->execute([$clientId]);
    $agg = $s->fetch();

    $total  = (int)$agg['total'];
    $wins   = (int)$agg['wins'];
    $losses = $total - $wins;
    $taux   = $total > 0 ? round($wins / $total * 100) : 0;
    $points = $wins * 50 + $losses * 10;   // 50 pts/gain, 10 pts/partie

    // Rang fidélité
    $rang = match(true) {
        $points >= 600 => ['label' => 'PLATINE', 'emoji' => '💎'],
        $points >= 300 => ['label' => 'OR',      'emoji' => '👑'],
        $points >= 100 => ['label' => 'ARGENT',  'emoji' => '⭐'],
        default        => ['label' => 'BRONZE',  'emoji' => '🥉'],
    };

    // 20 dernières parties
    $stmt2 = $db->prepare(
        "SELECT p.gagne, p.score, p.duree_partie,
                DATE_FORMAT(p.date_partie, '%d/%m/%Y') AS date_partie,
                DATE_FORMAT(p.date_partie, '%H:%i')    AS heure,
                COALESCE(p.gain_libelle, d.libelle, 'Perdu') AS dotation,
                j.nom AS jeu,
                j.type_jeu
         FROM parties p
         LEFT JOIN jeux      j ON p.jeu_id          = j.id
         LEFT JOIN dotations d ON p.dotation_gagnee = d.id
         WHERE p.client_id = ?
         ORDER BY p.date_partie DESC
         LIMIT 20"
    );
    $stmt2->execute([$clientId]);
    $parties = $stmt2->fetchAll();

    foreach ($parties as &$p) {
        $p['gagne'] = (int)$p['gagne'];
    }
    unset($p);

    // Éligibilité 2ème chance :
    //  - a joué aujourd'hui à la borne (partie normale) OU la borne a marqué dc_app_date = today
    //  - n'a pas encore joué sa 2ème chance aujourd'hui
    $s1 = $db->prepare("SELECT COUNT(*) FROM parties WHERE client_id = ? AND DATE(date_partie) = CURDATE() AND deuxieme_chance = 0");
    $s1->execute([$clientId]);
    $aJoueAujourdhui = $s1->fetchColumn() > 0;

    // Fallback : la borne a explicitement accordé la 2ème chance via l'app
    if (!$aJoueAujourdhui) {
        $aJoueAujourdhui = ($client['dc_app_date'] === date('Y-m-d'));
    }

    $s2 = $db->prepare("SELECT COUNT(*) FROM parties WHERE client_id = ? AND DATE(date_partie) = CURDATE() AND deuxieme_chance = 1");
    $s2->execute([$clientId]);
    $aDejaDeuxiemeChance = $s2->fetchColumn() > 0;

    echo json_encode([
        'success'              => true,
        'client'               => $client,
        'stats'                => [
            'total'   => $total,
            'wins'    => $wins,
            'losses'  => $losses,
            'taux'    => $taux,
            'points'  => $points,
            'rang'    => $rang,
        ],
        'parties'              => $parties,
        'peut_deuxieme_chance' => $aJoueAujourdhui && !$aDejaDeuxiemeChance,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
