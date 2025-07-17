<?php

namespace App\Repository;

use App\Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): array
    {
        $this->db->execute(
            "INSERT INTO users (name, email) VALUES (:name, :email)",
            [':name' => $data['name'], ':email' => $data['email']]
        );

        return $this->find($this->db->lastInsertId());
    }

    public function find(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT id, name, email FROM users WHERE id = :id",
            [':id' => $id]
        );

        return $result[0] ?? null;
    }

    public function findAll(): array
    {
        return $this->db->query("SELECT id, name, email FROM users");
    }

    public function deleteAll(): bool
    {
        $this->db->execute("DELETE FROM users");
        return true;
    }

    public function findDuplicates(array $data): array
    {
        return $this->db->query(
            "SELECT * FROM users WHERE name = :name OR email = :email",
            [':name' => $data['name'], ':email' => $data['email']]
        );
    }

    public function checkFieldExists(string $field, string $value): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE $field = :value",
            [':value' => $value]
        );

        return (int)($result[0]['count'] ?? 0) > 0;
    }
}