<?php
// Bootstrap PHPUnit — stubs sans Apache/MySQL, puis chargement de rate_limit.php
define('PHPUNIT_RUNNING', true);

// ── Stub getDB (SQLite en mémoire) ──────────────────────────────────────────
if (!function_exists('getDB')) {
    function getDB(): PDO {
        static $db = null;
        if ($db) return $db;
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prenom TEXT, nom TEXT, email TEXT UNIQUE,
                mot_de_passe TEXT, actif INTEGER DEFAULT 1,
                date_inscription TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS administrateurs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                login TEXT UNIQUE, mot_de_passe TEXT, role TEXT DEFAULT 'admin'
            );
            CREATE TABLE IF NOT EXISTS admin_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER, token TEXT UNIQUE,
                expire_a TEXT, cree_le TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT, endpoint TEXT, tentatives INTEGER DEFAULT 0,
                bloque_jusqu_a TEXT, derniere_tentative TEXT
            );
            CREATE TABLE IF NOT EXISTS reset_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER, token TEXT UNIQUE,
                expire_a TEXT, utilise INTEGER DEFAULT 0
            );
            CREATE TABLE IF NOT EXISTS jeux (
                id INTEGER PRIMARY KEY AUTOINCREMENT, nom TEXT
            );
            CREATE TABLE IF NOT EXISTS dotations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jeu_id INTEGER, libelle TEXT, probabilite REAL,
                couleur TEXT, stock INTEGER DEFAULT -1
            );
            CREATE TABLE IF NOT EXISTS parties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER, jeu_id INTEGER, dotation_gagnee INTEGER,
                gagne INTEGER DEFAULT 0, score INTEGER,
                deuxieme_chance INTEGER DEFAULT 0, duree_partie INTEGER,
                code_barre_scan TEXT, gain_libelle TEXT,
                date_partie TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
        return $db;
    }
}

// ── Stub des fonctions qui interrompent l'exécution ─────────────────────────
if (!function_exists('logEvent')) {
    function logEvent(string $level, string $action, array $ctx = []): void {}
}

// Simuler une IP pour rate_limit
$_SERVER['REMOTE_ADDR']  = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = '';

// ── Charger rate_limit.php (expose getCsrfToken, purgeExpired, etc.) ─────────
// requireAdminAuth et checkRateLimit appellent http_response_code/exit en cas d'échec.
// On les écrase après le require pour les tests.
require_once dirname(__DIR__) . '/api/rate_limit.php';
