<?php
// ================================================================
// api/logger.php - Logs JSON structurés
// Usage : logEvent('info', 'login', ['email' => $email])
// Fichier : /var/log/borne/app.log (max 5 Mo, rotation auto)
// ================================================================

define('LOG_FILE', '/var/log/borne/app.log');
define('LOG_MAX_BYTES', 5 * 1024 * 1024); // 5 Mo

function logEvent(string $level, string $action, array $ctx = []): void {
    // Rotation si le fichier dépasse la taille max
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > LOG_MAX_BYTES) {
        @rename(LOG_FILE, LOG_FILE . '.' . date('Ymd_His') . '.bak');
    }

    $entry = json_encode([
        'ts'     => date('c'),           // ISO 8601
        'level'  => strtoupper($level),
        'action' => $action,
        'ip'     => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-',
        'ctx'    => $ctx,
    ], JSON_UNESCAPED_UNICODE);

    @file_put_contents(LOG_FILE, $entry . "\n", FILE_APPEND | LOCK_EX);
}
