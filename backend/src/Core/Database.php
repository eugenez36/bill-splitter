<?php

namespace App\Core;

class Database
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        $connString = "mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'];

        $this->pdo = new \PDO($connString, $config['user'], $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    public function query(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $query, array $params = []): int
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }
}