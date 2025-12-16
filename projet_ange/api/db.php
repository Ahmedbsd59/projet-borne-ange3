<?php
// ================================================================
// api/db.php - Connexion à la base de données MySQL
// Lycée César Baggio - BTS CIEL IR - Étudiant 3
// ================================================================

// ⚙️  Configuration via variables d'environnement Docker (.env)
//     En local sans Docker : valeurs fallback utilisées automatiquement
define('DB_HOST',    getenv('MYSQL_HOST')     ?: 'db');
define('DB_NAME',    getenv('MYSQL_DATABASE') ?: 'borne_interactive');
define('DB_USER',    getenv('MYSQL_USER')     ?: 'borne_user');
define('DB_PASS',    getenv('MYSQL_PASSWORD') ?: 'borne_pass_2026');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Connexion BDD impossible']));
        }
    }
    return $pdo;
}

// Headers CORS — origines autorisées uniquement
$allowed = [
    'http://localhost',
    'http://localhost:80',
    'http://127.0.0.1',
    'https://macbook-air-de-ahmed.tailfaf4e9.ts.net',
    'https://borne.tailfaf4e9.ts.net',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Même domaine (borne et dashboard servis par Apache) : pas de header CORS nécessaire
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
