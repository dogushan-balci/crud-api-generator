<?php

namespace CRUDAPIGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CRUDAPIGenerator\Database\Database;
use PDO;

class DatabaseTest extends TestCase
{
    private Database $database;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=test_db',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->database = new Database([
            'host' => 'localhost',
            'dbname' => 'test_db',
            'username' => 'root',
            'password' => ''
        ]);

        // Test tablosunu oluştur
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function tearDown(): void
    {
        // Test tablosunu sil
        $this->pdo->exec("DROP TABLE IF EXISTS test_table");
    }

    public function testGetTables(): void
    {
        $tables = $this->database->getTables();
        $this->assertIsArray($tables);
        $this->assertContains('test_table', $tables);
    }

    public function testGetTableStructure(): void
    {
        $structure = $this->database->getTableStructure('test_table');
        
        $this->assertIsArray($structure);
        $this->assertArrayHasKey('id', $structure);
        $this->assertArrayHasKey('name', $structure);
        $this->assertArrayHasKey('email', $structure);
        $this->assertArrayHasKey('created_at', $structure);

        $this->assertEquals('int', $structure['id']['type']);
        $this->assertEquals('varchar', $structure['name']['type']);
        $this->assertEquals('varchar', $structure['email']['type']);
        $this->assertEquals('timestamp', $structure['created_at']['type']);
    }

    public function testGetPrimaryKey(): void
    {
        $primaryKey = $this->database->getPrimaryKey('test_table');
        $this->assertEquals('id', $primaryKey);
    }

    public function testGetConnection(): void
    {
        $connection = $this->database->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testInvalidTableStructure(): void
    {
        $this->expectException(\PDOException::class);
        $this->database->getTableStructure('non_existent_table');
    }

    public function testInvalidPrimaryKey(): void
    {
        $this->expectException(\PDOException::class);
        $this->database->getPrimaryKey('non_existent_table');
    }
} 