<?php

namespace EMS\Framework\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    public ?PDO $pdo = null;

    private function __construct(string $connectionString, string $username, string $password)
    {
        try {
            $this->pdo = new PDO($connectionString, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            error_log("Database Connected successfully\n");
        } catch (PDOException $e) {
            // Handle connection errors
            error_log("Connection failed: " . $e->getMessage() . "\n");
            throw $e;
        }
    }

    public static function create(string $connectionString, string $username, string $password): static
    {
        if (static::$instance === null) {
            static::$instance = new static($connectionString, $username, $password);
        }

        return static::$instance;
    }

    public static function getConnection(): static
    {
        return static::$instance;
    }
}
