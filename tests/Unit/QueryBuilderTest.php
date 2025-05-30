<?php

namespace CRUDAPIGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CRUDAPIGenerator\Database\QueryBuilder;
use PDO;

class QueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=test_db',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->queryBuilder = new QueryBuilder($this->pdo, 'test_table');

        // Test tablosunu oluştur
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS test_table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Test verilerini ekle
        $this->pdo->exec("
            INSERT INTO test_table (name, email) VALUES
            ('John Doe', 'john@example.com'),
            ('Jane Doe', 'jane@example.com'),
            ('Bob Smith', 'bob@example.com')
        ");
    }

    protected function tearDown(): void
    {
        // Test tablosunu sil
        $this->pdo->exec("DROP TABLE IF EXISTS test_table");
    }

    public function testSelect(): void
    {
        $results = $this->queryBuilder->select();
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithWhere(): void
    {
        $results = $this->queryBuilder
            ->where('name', '=', 'John Doe')
            ->select();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function testSelectWithOrderBy(): void
    {
        $results = $this->queryBuilder
            ->orderBy('name', 'ASC')
            ->select();

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertEquals('Bob Smith', $results[0]['name']);
    }

    public function testSelectWithLimit(): void
    {
        $results = $this->queryBuilder
            ->limit(2)
            ->select();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testSelectWithOffset(): void
    {
        $results = $this->queryBuilder
            ->limit(1)
            ->offset(1)
            ->select();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Jane Doe', $results[0]['name']);
    }

    public function testInsert(): void
    {
        $id = $this->queryBuilder->insert([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $result = $this->queryBuilder
            ->where('id', '=', $id)
            ->select();

        $this->assertCount(1, $result);
        $this->assertEquals('Test User', $result[0]['name']);
    }

    public function testUpdate(): void
    {
        $affected = $this->queryBuilder
            ->where('name', '=', 'John Doe')
            ->update([
                'name' => 'John Updated'
            ]);

        $this->assertEquals(1, $affected);

        $result = $this->queryBuilder
            ->where('name', '=', 'John Updated')
            ->select();

        $this->assertCount(1, $result);
    }

    public function testDelete(): void
    {
        $affected = $this->queryBuilder
            ->where('name', '=', 'John Doe')
            ->delete();

        $this->assertEquals(1, $affected);

        $result = $this->queryBuilder
            ->where('name', '=', 'John Doe')
            ->select();

        $this->assertCount(0, $result);
    }

    public function testComplexQuery(): void
    {
        $results = $this->queryBuilder
            ->where('name', 'LIKE', '%Doe%')
            ->orderBy('name', 'ASC')
            ->limit(1)
            ->select();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Jane Doe', $results[0]['name']);
    }
}