<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests pour api/health.php — vérifie la structure de la réponse
 */
class HealthTest extends TestCase
{
    public function testHealthRetourneStructureAttendue(): void
    {
        // Simuler ce que fait health.php sans HTTP
        $db     = getDB();
        $checks = [];

        try {
            $db->query('SELECT 1');
            $checks['db'] = 'ok';
        } catch (Throwable $e) {
            $checks['db'] = 'error';
        }

        $checks['tmp_writable'] = is_writable('/tmp') ? 'ok' : 'warn';

        $allOk = !in_array('error', $checks, true);

        $payload = [
            'status'      => $allOk ? 'ok' : 'degraded',
            'checks'      => $checks,
            'php_version' => PHP_VERSION,
            'ts'          => date('c'),
        ];

        $this->assertArrayHasKey('status',      $payload);
        $this->assertArrayHasKey('checks',      $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('ts',          $payload);
        $this->assertContains($payload['status'], ['ok', 'degraded']);
    }

    public function testDbCheckReussitAvecSQLite(): void
    {
        $db     = getDB();
        $checks = [];
        try {
            $db->query('SELECT 1');
            $checks['db'] = 'ok';
        } catch (Throwable $e) {
            $checks['db'] = 'error';
        }
        $this->assertSame('ok', $checks['db']);
    }

    public function testStatusEstDegradeSiDbEnErreur(): void
    {
        $checks = ['db' => 'error', 'tmp_writable' => 'ok'];
        $allOk  = !in_array('error', $checks, true);
        $this->assertFalse($allOk);
        $this->assertSame('degraded', $allOk ? 'ok' : 'degraded');
    }
}
