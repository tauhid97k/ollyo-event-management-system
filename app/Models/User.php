<?php

namespace App\Models;

use EMS\Framework\Database\Connection;
use PDO;
use PDOException;

class User
{
    public ?int $id = null;
    public string $name;
    public string $email;
    public string $created_at;
    public string $updated_at;

    // Save to database
    public function save(array $data): bool
    {
        try {
            $connection = Connection::getConnection();
            $pdo = $connection->pdo;

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password)  -- Removed 'date'
                VALUES (:name, :email, :password)
            ");

            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);

            $this->id = (int)$pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage()); // Log the error!
            return false;
        }
    }

    // Get user by email
    public function findByEmail(string $email)
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?? null;
    }

    // Construct auth user
    public function getUser(int $id)
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = new User();
            $user->id = $userData['id'];
            $user->name = $userData['name'];
            $user->email = $userData['email'];
            $user->created_at = $userData['created_at'];
            $user->updated_at = $userData['updated_at'];

            return $user;
        }

        return null;
    }
}
