<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests pour la logique de client_detail.php
 */
class ClientDetailTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->db->exec("DELETE FROM parties");
        $this->db->exec("DELETE FROM clients");
        $this->db->exec("DELETE FROM jeux");
        $this->db->exec("INSERT INTO jeux (id, nom) VALUES (1, 'Roue')");
    }

    private function insertClient(string $email): int
    {
        $this->db->prepare(
            "INSERT INTO clients (prenom, nom, email, mot_de_passe, actif) VALUES (?,?,?,?,1)"
        )->execute(['Alice', 'Martin', $email, password_hash('pass', PASSWORD_BCRYPT)]);
        return (int)$this->db->lastInsertId();
    }

    private function insertPartie(int $clientId, int $gagne): void
    {
        $this->db->prepare(
            "INSERT INTO parties (client_id, jeu_id, gagne, score) VALUES (?,1,?,?)"
        )->execute([$clientId, $gagne, rand(100, 999)]);
    }

    public function testClientInexistantRetourneRienOuFalse(): void
    {
        $stmt = $this->db->prepare("SELECT id FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([9999]);
        $this->assertFalse($stmt->fetch());
    }

    public function testClientAvecPartiesRetourneLeBonNombre(): void
    {
        $id = $this->insertClient('alice@test.com');
        $this->insertPartie($id, 1);
        $this->insertPartie($id, 0);
        $this->insertPartie($id, 1);

        $stmt = $this->db->prepare("SELECT COUNT(*) AS nb FROM parties WHERE client_id = ?");
        $stmt->execute([$id]);
        $nb = (int)$stmt->fetch()['nb'];
        $this->assertSame(3, $nb);
    }

    public function testStatsTauxDeGainCalculeCorrectement(): void
    {
        $id = $this->insertClient('bob@test.com');
        $this->insertPartie($id, 1);
        $this->insertPartie($id, 1);
        $this->insertPartie($id, 0);
        $this->insertPartie($id, 1);

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(gagne) AS wins FROM parties WHERE client_id=?");
        $stmt->execute([$id]);
        $row     = $stmt->fetch();
        $winRate = round($row['wins'] / $row['total'] * 100);
        $this->assertSame(75, $winRate);
    }

    public function testClientSansPartiesRetourneStats0(): void
    {
        $id = $this->insertClient('novice@test.com');

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(gagne),0) AS wins FROM parties WHERE client_id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $this->assertSame(0, (int)$row['total']);
        $this->assertSame(0, (int)$row['wins']);
    }
}
