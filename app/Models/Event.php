<?php // Event.php

namespace App\Models;

use EMS\Framework\Database\Connection;
use PDO;
use PDOException;

class Event
{
    public int $id;
    public int $user_id;
    public string $title;
    public string $date;
    public string $description;
    public string $status;
    public int $limit;
    public ?string $thumbnail = null;
    public string $created_at;
    public string $updated_at;

    public function save(array $data): bool
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (user_id, title, date, description, status, `limit`, thumbnail) 
                VALUES (:user_id, :title, :date, :description, :status, :limit, :thumbnail)
            ");

            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':title' => $data['title'],
                ':date' => $data['date'],
                ':description' => $data['description'],
                ':status' => $data['status'],
                ':limit' => $data['limit'],
                ':thumbnail' => $data['thumbnail'] ?? null, // Handle nullable thumbnail
            ]);

            $this->id = (int)$pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // Get events with pagination and search
    public static function getEventsWithPagination(int $userId, int $perPage, int $page, ?string $search = null): array
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        $perPage = (int) $perPage;
        $page = (int) $page;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM events WHERE user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($search) {
            $sql .= " AND title LIKE :search";
            $params[':search'] = "%" . $search . "%";
        }

        // Injecting LIMIT and OFFSET directly as integers
        $sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Models\Event');
    }

    // Get total events
    public static function getTotalEvents(int $userId, ?string $search = null): int
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        $sql = "SELECT COUNT(*) FROM events WHERE user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($search) {
            $sql .= " AND title LIKE :search";
            $params[':search'] = "%" . $search . "%";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    // Get total registered Users
    public static function getRegistrationCount(int $eventId): int
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM event_registrations 
        WHERE event_id = :event_id
    ");

        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchColumn();
    }

    // Allow registration for the event
    public function registerUser(int $userId): bool
    {
        $connection = Connection::getConnection();
        $pdo = $connection->pdo;

        try {
            $stmt = $pdo->prepare("
            INSERT INTO event_registrations (event_id, user_id)
            VALUES (:event_id, :user_id)
        ");

            $stmt->execute([
                ':event_id' => $this->id,
                ':user_id' => $userId,
            ]);

            return true;
        } catch (PDOException $e) {
            // Handle duplicate registration or other errors
            if ($e->getCode() == '23000') {
                throw new \Exception("You are already registered for this event.");
            }
            error_log($e->getMessage());
            return false;
        }
    }
}
