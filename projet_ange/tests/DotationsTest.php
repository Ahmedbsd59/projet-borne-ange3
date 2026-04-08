<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests pour la logique des dotations (probabilités, stock, CRUD)
 */
class DotationsTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->db->exec("DELETE FROM dotations");
        $this->db->exec("DELETE FROM jeux");
        $this->db->exec("INSERT INTO jeux (id, nom) VALUES (1, 'Roue de la Chance')");
        $this->db->exec("
            INSERT INTO dotations (jeu_id, libelle, probabilite, couleur, stock) VALUES
            (1, 'Bon cadeau 10€', 10.0, '#ff0000', 50),
            (1, 'Remise 20%',    30.0, '#00ff00', -1),
            (1, 'Perdu',         60.0, '#888888', -1)
        ");
    }

    public function testTotalProbabilitesEgale100(): void
    {
        $rows  = $this->db->query("SELECT SUM(probabilite) AS total FROM dotations WHERE jeu_id=1")->fetch();
        $total = (float)$rows['total'];
        $this->assertEqualsWithDelta(100.0, $total, 0.01, 'La somme des probabilités doit être 100%');
    }

    public function testStockNegatifSignifieIllimite(): void
    {
        $rows = $this->db->query("SELECT * FROM dotations WHERE stock < 0")->fetchAll();
        foreach ($rows as $r) {
            $this->assertSame(-1, (int)$r['stock'], 'stock=-1 signifie illimité');
        }
    }

    public function testDotationAvecStockZeroEstEpuisee(): void
    {
        $this->db->exec("UPDATE dotations SET stock=0 WHERE libelle='Bon cadeau 10€'");
        $row = $this->db->query(
            "SELECT stock FROM dotations WHERE libelle='Bon cadeau 10€'"
        )->fetch();
        $this->assertSame(0, (int)$row['stock']);
    }

    public function testCreationDotationPersistee(): void
    {
        $this->db->prepare(
            "INSERT INTO dotations (jeu_id, libelle, probabilite, couleur, stock) VALUES (?,?,?,?,?)"
        )->execute([1, 'Goodies', 5.0, '#0000ff', 10]);

        $row = $this->db->query(
            "SELECT * FROM dotations WHERE libelle='Goodies'"
        )->fetch();

        $this->assertNotFalse($row);
        $this->assertSame('Goodies', $row['libelle']);
        $this->assertEqualsWithDelta(5.0, (float)$row['probabilite'], 0.001);
    }

    public function testSuppressionDotation(): void
    {
        $row = $this->db->query("SELECT id FROM dotations WHERE libelle='Bon cadeau 10€'")->fetch();
        $this->db->prepare("DELETE FROM dotations WHERE id=?")->execute([$row['id']]);

        $check = $this->db->query(
            "SELECT id FROM dotations WHERE libelle='Bon cadeau 10€'"
        )->fetch();
        $this->assertFalse($check);
    }

    public function testMiseAJourProbabilite(): void
    {
        $this->db->prepare(
            "UPDATE dotations SET probabilite=? WHERE libelle=?"
        )->execute([15.0, 'Bon cadeau 10€']);

        $row = $this->db->query(
            "SELECT probabilite FROM dotations WHERE libelle='Bon cadeau 10€'"
        )->fetch();
        $this->assertEqualsWithDelta(15.0, (float)$row['probabilite'], 0.001);
    }
}
