<?php

namespace CRUDAPIGenerator\API;

use CRUDAPIGenerator\Database\Database;
use CRUDAPIGenerator\Database\QueryBuilder;

class EndpointGenerator
{
    private Database $database;
    private QueryBuilder $queryBuilder;
    private string $table;
    private array $structure;
    private ?string $primaryKey;

    public function __construct(Database $database, string $table)
    {
        $this->database = $database;
        $this->table = $table;
        $this->structure = $database->getTableStructure($table);
        $this->primaryKey = $database->getPrimaryKey($table);
        $this->queryBuilder = new QueryBuilder($database->getConnection(), $table);
    }

    public function generateListEndpoint(): array
    {
        $result = $this->queryBuilder->select();
        return [
            'status' => 'success',
            'data' => $result
        ];
    }

    public function generateGetEndpoint(int $id): array
    {
        $result = $this->queryBuilder
            ->where($this->primaryKey, '=', $id)
            ->select();

        if (empty($result)) {
            return [
                'status' => 'error',
                'message' => 'Record not found'
            ];
        }

        return [
            'status' => 'success',
            'data' => $result[0]
        ];
    }

    public function generateCreateEndpoint(array $data): array
    {
        // Veri doğrulama
        $validationResult = $this->validateData($data);
        if (!$validationResult['valid']) {
            return [
                'status' => 'error',
                'message' => $validationResult['message']
            ];
        }

        // Veri temizleme
        $cleanData = $this->sanitizeData($data);

        // Kayıt oluşturma
        $id = $this->queryBuilder->insert($cleanData);

        return [
            'status' => 'success',
            'message' => 'Record created successfully',
            'data' => ['id' => $id]
        ];
    }

    public function generateUpdateEndpoint(int $id, array $data): array
    {
        // Veri doğrulama
        $validationResult = $this->validateData($data, true);
        if (!$validationResult['valid']) {
            return [
                'status' => 'error',
                'message' => $validationResult['message']
            ];
        }

        // Veri temizleme
        $cleanData = $this->sanitizeData($data);

        // Kayıt güncelleme
        $affected = $this->queryBuilder
            ->where($this->primaryKey, '=', $id)
            ->update($cleanData);

        if ($affected === 0) {
            return [
                'status' => 'error',
                'message' => 'Record not found'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Record updated successfully'
        ];
    }

    public function generateDeleteEndpoint(int $id): array
    {
        $affected = $this->queryBuilder
            ->where($this->primaryKey, '=', $id)
            ->delete();

        if ($affected === 0) {
            return [
                'status' => 'error',
                'message' => 'Record not found'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Record deleted successfully'
        ];
    }

    private function validateData(array $data, bool $isUpdate = false): array
    {
        foreach ($this->structure as $column => $info) {
            // Primary key kontrolü
            if ($column === $this->primaryKey && !$isUpdate) {
                continue;
            }

            // Zorunlu alan kontrolü
            if ($info['nullable'] === false && !isset($data[$column])) {
                return [
                    'valid' => false,
                    'message' => "Field '{$column}' is required"
                ];
            }

            // Veri tipi kontrolü
            if (isset($data[$column])) {
                $typeCheck = $this->validateType($data[$column], $info['type']);
                if (!$typeCheck['valid']) {
                    return [
                        'valid' => false,
                        'message' => $typeCheck['message']
                    ];
                }
            }
        }

        return ['valid' => true];
    }

    private function validateType($value, string $type): array
    {
        switch ($type) {
            case 'int':
                if (!is_numeric($value)) {
                    return [
                        'valid' => false,
                        'message' => 'Value must be numeric'
                    ];
                }
                break;
            case 'varchar':
            case 'text':
                if (!is_string($value)) {
                    return [
                        'valid' => false,
                        'message' => 'Value must be string'
                    ];
                }
                break;
            case 'datetime':
                if (!strtotime($value)) {
                    return [
                        'valid' => false,
                        'message' => 'Invalid date format'
                    ];
                }
                break;
        }

        return ['valid' => true];
    }

    private function sanitizeData(array $data): array
    {
        $cleanData = [];
        foreach ($data as $key => $value) {
            if (isset($this->structure[$key])) {
                $cleanData[$key] = $this->sanitizeValue($value, $this->structure[$key]['type']);
            }
        }
        return $cleanData;
    }

    private function sanitizeValue($value, string $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'varchar':
            case 'text':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            case 'datetime':
                return date('Y-m-d H:i:s', strtotime($value));
            default:
                return $value;
        }
    }
} 