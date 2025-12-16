<?php
// ================================================================
// api/jeux_public.php — Liste publique des jeux + stats générales
// GET (aucune auth requise)
// → { success, jeux: [{id, nom, type_jeu, nb_parties, taux_gain, dotations}] }
// ================================================================
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false]); exit;
}

header('Cache-Control: public, max-age=120');

try {
    $db = getDB();

    $jeux = $db->query(
        "SELECT j.id, j.nom, j.type_jeu,
                COUNT(p.id)                                              AS nb_parties,
                ROUND(COALESCE(SUM(p.gagne),0) / NULLIF(COUNT(p.id),0) * 100, 1) AS taux_gain
         FROM jeux j
         LEFT JOIN parties p ON p.jeu_id = j.id
         WHERE j.actif = 1
         GROUP BY j.id
         ORDER BY j.id"
    )->fetchAll();

    $dotations = $db->query(
        "SELECT jeu_id, libelle, probabilite, couleur
         FROM dotations
         WHERE probabilite > 0
         ORDER BY jeu_id, probabilite DESC"
    )->fetchAll();

    $dotByJeu = [];
    foreach ($dotations as $d) {
        $dotByJeu[(int)$d['jeu_id']][] = [
            'libelle'     => $d['libelle'],
            'probabilite' => (float)$d['probabilite'],
            'couleur'     => $d['couleur'],
        ];
    }

    $icons = [
        'roue_chance' => '🎡',
        'memory'      => '🧠',
        'jackpot'     => '🎰',
    ];
    $colors = [
        'roue_chance' => ['#3B82F6', '#6366F1'],
        'memory'      => ['#10B981', '#059669'],
        'jackpot'     => ['#F59E0B', '#EF4444'],
    ];

    foreach ($jeux as &$j) {
        $j['nb_parties'] = (int)$j['nb_parties'];
        $j['taux_gain']  = (float)$j['taux_gain'];
        $j['icon']       = $icons[$j['type_jeu']] ?? '🎮';
        $j['colors']     = $colors[$j['type_jeu']] ?? ['#7C3AED', '#A855F7'];
        $j['dotations']  = $dotByJeu[(int)$j['id']] ?? [];
    }
    unset($j);

    echo json_encode(['success' => true, 'jeux' => $jeux]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
