<?php

namespace CRUDAPIGenerator\Database;

use PDO;
use PDOException;

class Migration
{
    private PDO $connection;
    private string $migrationsTable = 'migrations';

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->connection->exec($sql);
    }

    public function run(array $migrations): void
    {
        $this->connection->beginTransaction();

        try {
            $batch = $this->getNextBatchNumber();

            foreach ($migrations as $migration) {
                if (!$this->hasMigration($migration)) {
                    $this->runMigration($migration, $batch);
                }
            }

            $this->connection->commit();
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new PDOException("Migration hatası: " . $e->getMessage());
        }
    }

    private function getNextBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) as batch FROM {$this->migrationsTable}";
        $result = $this->connection->query($sql)->fetch(PDO::FETCH_ASSOC);
        return ($result['batch'] ?? 0) + 1;
    }

    private function hasMigration(string $migration): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function runMigration(string $migration, int $batch): void
    {
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration, $batch]);
    }

    public function rollback(): void
    {
        $this->connection->beginTransaction();

        try {
            $batch = $this->getLastBatchNumber();
            $migrations = $this->getMigrationsForBatch($batch);

            foreach (array_reverse($migrations) as $migration) {
                $this->rollbackMigration($migration);
            }

            $this->connection->commit();
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new PDOException("Rollback hatası: " . $e->getMessage());
        }
    }

    private function getLastBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) as batch FROM {$this->migrationsTable}";
        $result = $this->connection->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $result['batch'] ?? 0;
    }

    private function getMigrationsForBatch(int $batch): array
    {
        $sql = "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function rollbackMigration(string $migration): void
    {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration]);
    }
} 