<?php

namespace CRUDAPIGenerator\Core;

class APIGenerator
{
    private Database $database;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->database = new Database($config);
    }

    public function generate(): void
    {
        $tables = $this->database->getTables();
        
        foreach ($tables as $table) {
            $this->generateTableAPI($table);
        }
    }

    private function generateTableAPI(string $table): void
    {
        $structure = $this->database->getTableStructure($table);
        $primaryKey = $this->database->getPrimaryKey($table);

        // API endpoint'lerini oluştur
        $this->generateEndpoints($table, $structure, $primaryKey);
    }

    private function generateEndpoints(string $table, array $structure, ?string $primaryKey): void
    {
        // GET /api/{table}
        $this->generateListEndpoint($table, $structure);
        
        // GET /api/{table}/{id}
        if ($primaryKey) {
            $this->generateGetEndpoint($table, $primaryKey);
        }
        
        // POST /api/{table}
        $this->generateCreateEndpoint($table, $structure);
        
        // PUT /api/{table}/{id}
        if ($primaryKey) {
            $this->generateUpdateEndpoint($table, $structure, $primaryKey);
        }
        
        // DELETE /api/{table}/{id}
        if ($primaryKey) {
            $this->generateDeleteEndpoint($table, $primaryKey);
        }
    }

    private function generateListEndpoint(string $table, array $structure): void
    {
        // TODO: Implement list endpoint generation
    }

    private function generateGetEndpoint(string $table, string $primaryKey): void
    {
        // TODO: Implement get endpoint generation
    }

    private function generateCreateEndpoint(string $table, array $structure): void
    {
        // TODO: Implement create endpoint generation
    }

    private function generateUpdateEndpoint(string $table, array $structure, string $primaryKey): void
    {
        // TODO: Implement update endpoint generation
    }

    private function generateDeleteEndpoint(string $table, string $primaryKey): void
    {
        // TODO: Implement delete endpoint generation
    }
} 