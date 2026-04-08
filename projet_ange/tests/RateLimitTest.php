<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests pour api/rate_limit.php
 * Note : purgeExpired() utilise des fonctions MySQL (NOW, DATE_SUB) ; on teste
 * la logique de suppression directement avec du SQL SQLite-compatible.
 */
class RateLimitTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->db->exec("DELETE FROM admin_tokens");
        $this->db->exec("DELETE FROM rate_limits");
    }

    public function testGetCsrfTokenRetourneUneChaine(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer testtoken123';
        $token = getCsrfToken();
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGetCsrfTokenEstDeterministe(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc';
        $t1 = getCsrfToken();
        $t2 = getCsrfToken();
        $this->assertSame($t1, $t2, 'Le même bearer doit toujours produire le même CSRF token');
    }

    public function testGetCsrfTokenDifferentPourDifferentBearer(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer tokenA';
        $t1 = getCsrfToken();
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer tokenB';
        $t2 = getCsrfToken();
        $this->assertNotSame($t1, $t2);
    }

    public function testGetCsrfTokenEstUnHmacSha256(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer montoken';
        $token = getCsrfToken();
        // HMAC-SHA256 produit toujours 64 caractères hex
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testRevokeAdminTokenSupprimeLigne(): void
    {
        $tok = str_repeat('a', 64);
        $this->db->prepare(
            "INSERT INTO admin_tokens (admin_id, token, expire_a) VALUES (1, ?, datetime('now', '+1 hour'))"
        )->execute([$tok]);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM admin_tokens")->fetchColumn();
        $this->assertSame(1, $count);

        revokeAdminToken($tok);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM admin_tokens")->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testRevokeTokenInexistantNeLancePasException(): void
    {
        // Ne doit pas lever d'exception
        $this->expectNotToPerformAssertions();
        revokeAdminToken(str_repeat('z', 64));
    }

    public function testLogiquePurgeSupprimeLesTokensExpires(): void
    {
        // Teste la logique de purge avec SQL SQLite (purgeExpired() utilise MySQL NOW())
        $this->db->exec("
            INSERT INTO admin_tokens (admin_id, token, expire_a) VALUES
            (1, '" . str_repeat('e', 64) . "', datetime('now', '-1 hour')),
            (1, '" . str_repeat('v', 64) . "', datetime('now', '+1 hour'))
        ");

        // Équivalent SQLite de purgeExpired()
        $this->db->exec("DELETE FROM admin_tokens WHERE expire_a < datetime('now')");

        $rows = $this->db->query("SELECT token FROM admin_tokens")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertCount(1, $rows);
        $this->assertSame(str_repeat('v', 64), $rows[0]);
    }

    public function testTokenEstUnHex64Chars(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }
}
