<?php

namespace App\Models;

use EMS\Framework\Database\Connection;
use PDO;
use PDOException;

class User
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private ?PDO $db = null;

    public function __construct()
    {
        $connection = Connection::getConnection();
        $this->db = $connection->pdo; // Access the PDO instance
    }

    // Save to database
    public function save(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password)  -- Removed 'date'
                VALUES (:name, :email, :password)
            ");

            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);

            $this->id = (int)$this->db->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage()); // Log the error!
            return false;
        }
    }

    // Find by email
    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get id
    public function getId(): ?int
    {
        return $this->id;
    }

    // Get Name
    public function getName(): string
    {
        return $this->name;
    }

    // Get Email
    public function getEmail(): string
    {
        return $this->email;
    }
}
