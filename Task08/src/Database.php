<?php

declare(strict_types=1);

/**
 * Возвращает путь к файлу базы данных.
 */
function getDatabasePath(): string
{
    return __DIR__ . '/../db/cold-hot.db';
}

/**
 * Инициализирует соединение с базой данных.
 */
function getConnection(): PDO
{
    static $pdo = null;
    
    if ($pdo === null) {
        $dbPath = getDatabasePath();
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Создаём таблицы если не существуют
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                secret_number TEXT NOT NULL,
                outcome TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                attempt_number INTEGER NOT NULL,
                guess TEXT NOT NULL,
                hints TEXT NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games(id)
            )
        ');
    }
    
    return $pdo;
}

/**
 * Создаёт новую игру.
 */
function createGame(string $playerName, string $secretNumber): int
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('
        INSERT INTO games (player_name, secret_number)
        VALUES (:player_name, :secret_number)
    ');
    $stmt->execute([
        ':player_name' => $playerName,
        ':secret_number' => $secretNumber
    ]);
    
    return (int) $pdo->lastInsertId();
}

/**
 * Обновляет результат игры.
 */
function updateGameOutcome(int $gameId, string $outcome): void
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('UPDATE games SET outcome = :outcome WHERE id = :id');
    $stmt->execute([':outcome' => $outcome, ':id' => $gameId]);
}

/**
 * Сохраняет попытку.
 */
function saveAttempt(int $gameId, int $attemptNumber, string $guess, string $hints): void
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('
        INSERT INTO attempts (game_id, attempt_number, guess, hints)
        VALUES (:game_id, :attempt_number, :guess, :hints)
    ');
    $stmt->execute([
        ':game_id' => $gameId,
        ':attempt_number' => $attemptNumber,
        ':guess' => $guess,
        ':hints' => $hints
    ]);
}

/**
 * Получает все игры.
 */
function getAllGames(): array
{
    $pdo = getConnection();
    $stmt = $pdo->query('SELECT * FROM games ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получает игру по ID.
 */
function getGameById(int $gameId): ?array
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
    $stmt->execute([':id' => $gameId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ?: null;
}

/**
 * Получает все попытки для игры.
 */
function getAttemptsByGameId(int $gameId): array
{
    $pdo = getConnection();
    $stmt = $pdo->prepare('
        SELECT * FROM attempts
        WHERE game_id = :game_id
        ORDER BY attempt_number ASC
    ');
    $stmt->execute([':game_id' => $gameId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
