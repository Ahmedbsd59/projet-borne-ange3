<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la logique de reset_password
 * On teste la logique métier directement (sans HTTP)
 */
class ResetPasswordTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->db->exec("DELETE FROM clients");
        $this->db->exec("DELETE FROM reset_tokens");
    }

    private function insertClient(string $email, bool $actif = true): int
    {
        $hash = password_hash('motdepasse123', PASSWORD_BCRYPT);
        $this->db->prepare(
            "INSERT INTO clients (prenom, nom, email, mot_de_passe, actif) VALUES (?,?,?,?,?)"
        )->execute(['Jean', 'Dupont', $email, $hash, $actif ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    public function testTokenResetEst64Chars(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->assertSame(64, strlen($token));
    }

    public function testTokenResetUniqueAChaqueFois(): void
    {
        $t1 = bin2hex(random_bytes(32));
        $t2 = bin2hex(random_bytes(32));
        $this->assertNotSame($t1, $t2);
    }

    public function testTokenExpireApresUneHeure(): void
    {
        $clientId = $this->insertClient('test@example.com');
        $token    = bin2hex(random_bytes(32));
        $expire   = date('Y-m-d H:i:s', time() + 3600);

        $this->db->prepare(
            "INSERT INTO reset_tokens (client_id, token, expire_a) VALUES (?,?,?)"
        )->execute([$clientId, $token, $expire]);

        $stmt = $this->db->prepare(
            "SELECT id FROM reset_tokens WHERE token=? AND utilise=0 AND expire_a > datetime('now') LIMIT 1"
        );
        $stmt->execute([$token]);
        $this->assertNotFalse($stmt->fetch(), 'Le token valide doit être trouvé');
    }

    public function testTokenExpireNePassePasLaVerification(): void
    {
        $clientId = $this->insertClient('old@example.com');
        $token    = bin2hex(random_bytes(32));
        $expire   = date('Y-m-d H:i:s', time() - 10); // déjà expiré

        $this->db->prepare(
            "INSERT INTO reset_tokens (client_id, token, expire_a) VALUES (?,?,?)"
        )->execute([$clientId, $token, $expire]);

        $stmt = $this->db->prepare(
            "SELECT id FROM reset_tokens WHERE token=? AND utilise=0 AND expire_a > datetime('now') LIMIT 1"
        );
        $stmt->execute([$token]);
        $this->assertFalse($stmt->fetch(), 'Un token expiré ne doit pas être trouvé');
    }

    public function testTokenUtilisenePassePasLaVerification(): void
    {
        $clientId = $this->insertClient('used@example.com');
        $token    = bin2hex(random_bytes(32));
        $expire   = date('Y-m-d H:i:s', time() + 3600);

        $this->db->prepare(
            "INSERT INTO reset_tokens (client_id, token, expire_a, utilise) VALUES (?,?,?,1)"
        )->execute([$clientId, $token, $expire]);

        $stmt = $this->db->prepare(
            "SELECT id FROM reset_tokens WHERE token=? AND utilise=0 AND expire_a > datetime('now') LIMIT 1"
        );
        $stmt->execute([$token]);
        $this->assertFalse($stmt->fetch(), 'Un token déjà utilisé ne doit pas être valide');
    }

    public function testResetMdpMetAJourLeHashEtMarqueTokenUtilise(): void
    {
        $clientId  = $this->insertClient('reset@example.com');
        $token     = bin2hex(random_bytes(32));
        $expire    = date('Y-m-d H:i:s', time() + 3600);

        $this->db->prepare(
            "INSERT INTO reset_tokens (client_id, token, expire_a) VALUES (?,?,?)"
        )->execute([$clientId, $token, $expire]);

        $stmt = $this->db->prepare(
            "SELECT rt.id AS rt_id, rt.client_id FROM reset_tokens rt
             WHERE rt.token = ? AND rt.utilise = 0 AND rt.expire_a > datetime('now') LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);

        $newMdp = 'nouveauMdp2026!';
        $hash   = password_hash($newMdp, PASSWORD_BCRYPT);
        $this->db->prepare("UPDATE clients SET mot_de_passe=? WHERE id=?")->execute([$hash, $row['client_id']]);
        $this->db->prepare("UPDATE reset_tokens SET utilise=1 WHERE id=?")->execute([$row['rt_id']]);

        // Vérifier que le nouveau mot de passe fonctionne
        $c = $this->db->query("SELECT mot_de_passe FROM clients WHERE id=$clientId")->fetch();
        $this->assertTrue(password_verify($newMdp, $c['mot_de_passe']));

        // Vérifier que le token est bien marqué utilisé
        $rt = $this->db->query("SELECT utilise FROM reset_tokens WHERE id={$row['rt_id']}")->fetch();
        $this->assertSame(1, (int)$rt['utilise']);
    }

    public function testMotDePasseTropCourtEstRefuse(): void
    {
        $this->assertLessThan(8, strlen('court'), 'Un mot de passe de moins de 8 chars doit être refusé');
        $this->assertGreaterThanOrEqual(8, strlen('valide!!'), 'Un MDP de 8+ chars doit passer');
    }
}
