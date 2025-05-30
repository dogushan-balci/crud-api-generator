<?php

namespace CRUDAPIGenerator\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=utf8mb4",
                    $this->config['host'],
                    $this->config['dbname']
                );

                self::$instance = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException("Veritabanı bağlantı hatası: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public function getTables(): array
    {
        $query = "SHOW TABLES";
        $stmt = $this->getConnection()->query($query);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTableStructure(string $table): array
    {
        $query = "DESCRIBE " . $table;
        $stmt = $this->getConnection()->query($query);
        return $stmt->fetchAll();
    }

    public function getPrimaryKey(string $table): ?string
    {
        $structure = $this->getTableStructure($table);
        foreach ($structure as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return null;
    }
} 