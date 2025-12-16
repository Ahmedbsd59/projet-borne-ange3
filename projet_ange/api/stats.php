<?php
// ================================================================
// api/stats.php - Statistiques globales pour le dashboard admin
// Paramètre optionnel : ?periode=jour|mois|annee|tout (défaut: tout)
// ================================================================
require_once 'db.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'GET uniquement']);
    exit;
}

requireAdminAuth();
header('Cache-Control: no-store, no-cache');

try {
    $db = getDB();

    // ── Filtre période pour les participations ──────────────────
    $periode = $_GET['periode'] ?? 'tout';
    $wherePartie = match($periode) {
        'jour'  => "WHERE DATE(date_partie) = CURDATE()",
        'mois'  => "WHERE YEAR(date_partie) = YEAR(NOW()) AND MONTH(date_partie) = MONTH(NOW())",
        'annee' => "WHERE YEAR(date_partie) = YEAR(NOW())",
        default => "",   // tout
    };

    // Participations (filtrées selon la période)
    $participations = (int)$db->query(
        "SELECT COUNT(*) FROM parties $wherePartie"
    )->fetchColumn();

    // Comptages par période (toujours renvoyés pour affichage)
    $part_jour  = (int)$db->query("SELECT COUNT(*) FROM parties WHERE DATE(date_partie) = CURDATE()")->fetchColumn();
    $part_mois  = (int)$db->query("SELECT COUNT(*) FROM parties WHERE YEAR(date_partie) = YEAR(NOW()) AND MONTH(date_partie) = MONTH(NOW())")->fetchColumn();
    $part_annee = (int)$db->query("SELECT COUNT(*) FROM parties WHERE YEAR(date_partie) = YEAR(NOW())")->fetchColumn();
    $part_tout  = (int)$db->query("SELECT COUNT(*) FROM parties")->fetchColumn();

    // Nombre de clients inscrits
    $leads = (int)$db->query("SELECT COUNT(*) FROM clients WHERE actif = 1")->fetchColumn();

    // Taux de gain global
    $gagnees   = (int)$db->query("SELECT COUNT(*) FROM parties WHERE gagne = 1")->fetchColumn();
    $taux_gain = $part_tout > 0 ? round($gagnees / $part_tout * 100, 1) : 0;

    // Sessions actives (parties des 15 dernières minutes)
    $sessions = (int)$db->query(
        "SELECT COUNT(DISTINCT client_id) FROM parties
         WHERE date_partie >= NOW() - INTERVAL 15 MINUTE AND client_id IS NOT NULL"
    )->fetchColumn();

    // QR / codes barres scannés aujourd'hui
    $qr_scans = (int)$db->query(
        "SELECT COUNT(*) FROM codes_barres WHERE DATE(date_scan) = CURDATE()"
    )->fetchColumn();

    // Clients avec un badge RFID associé
    $rfid_scans = (int)$db->query(
        "SELECT COUNT(*) FROM clients WHERE rfid_uid IS NOT NULL AND actif = 1"
    )->fetchColumn();

    // Variation semaine courante vs semaine précédente (parties)
    $prev_week = (int)$db->query(
        "SELECT COUNT(*) FROM parties
         WHERE date_partie >= DATE_SUB(NOW(), INTERVAL 14 DAY)
           AND date_partie <  DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )->fetchColumn();
    $this_week = (int)$db->query(
        "SELECT COUNT(*) FROM parties
         WHERE date_partie >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )->fetchColumn();
    $variation_parties = $prev_week > 0
        ? round(($this_week - $prev_week) / $prev_week * 100, 1)
        : 0;

    // Variation leads
    $leads_this = (int)$db->query(
        "SELECT COUNT(*) FROM clients WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )->fetchColumn();
    $leads_prev = (int)$db->query(
        "SELECT COUNT(*) FROM clients
         WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 14 DAY)
           AND date_inscription <  DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )->fetchColumn();
    $variation_leads = $leads_prev > 0
        ? round(($leads_this - $leads_prev) / $leads_prev * 100, 1)
        : 0;

    echo json_encode([
        'success'           => true,
        'periode'           => $periode,
        'participations'    => $participations,
        'part_jour'         => $part_jour,
        'part_mois'         => $part_mois,
        'part_annee'        => $part_annee,
        'part_tout'         => $part_tout,
        'leads'             => $leads,
        'taux_gain'         => $taux_gain,
        'sessions'          => max(1, $sessions),
        'qr_scans'          => $qr_scans,
        'rfid_scans'        => $rfid_scans,
        'variation_parties' => $variation_parties,
        'variation_leads'   => $variation_leads,
        'timestamp'         => date('Y-m-d H:i:s'),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
