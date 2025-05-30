<?php

namespace CRUDAPIGenerator\Database;

use PDO;

class QueryBuilder
{
    private PDO $connection;
    private string $table;
    private array $where = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(PDO $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function select(array $columns = ['*']): array
    {
        $sql = "SELECT " . implode(', ', $columns) . " FROM " . $this->table;
        $sql = $this->addWhereClause($sql);
        $sql = $this->addOrderByClause($sql);
        $sql = $this->addLimitOffsetClause($sql);

        $stmt = $this->connection->prepare($sql);
        $this->bindWhereValues($stmt);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($values);

        return (int) $this->connection->lastInsertId();
    }

    public function update(array $data): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "$column = ?";
        }

        $sql = sprintf(
            "UPDATE %s SET %s",
            $this->table,
            implode(', ', $set)
        );

        $sql = $this->addWhereClause($sql);
        $sql = $this->addOrderByClause($sql);
        $sql = $this->addLimitOffsetClause($sql);

        $stmt = $this->connection->prepare($sql);
        $this->bindWhereValues($stmt);
        $stmt->execute(array_values($data));

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM " . $this->table;
        $sql = $this->addWhereClause($sql);
        $sql = $this->addOrderByClause($sql);
        $sql = $this->addLimitOffsetClause($sql);

        $stmt = $this->connection->prepare($sql);
        $this->bindWhereValues($stmt);
        $stmt->execute();

        return $stmt->rowCount();
    }

    private function addWhereClause(string $sql): string
    {
        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as $where) {
                $conditions[] = sprintf(
                    "%s %s ?",
                    $where['column'],
                    $where['operator']
                );
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        return $sql;
    }

    private function addOrderByClause(string $sql): string
    {
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = sprintf(
                    "%s %s",
                    $order['column'],
                    $order['direction']
                );
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        return $sql;
    }

    private function addLimitOffsetClause(string $sql): string
    {
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset !== null) {
                $sql .= " OFFSET " . $this->offset;
            }
        }
        return $sql;
    }

    private function bindWhereValues(\PDOStatement $stmt): void
    {
        $paramIndex = 1;
        foreach ($this->where as $where) {
            $stmt->bindValue($paramIndex++, $where['value']);
        }
    }
} 