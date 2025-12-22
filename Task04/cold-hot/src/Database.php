<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Database;

use PDO;

/**
 * Возвращает путь к файлу базы данных.
 *
 * @return string Путь к файлу БД
 */
function getDatabasePath(): string
{
    $homeDir = getenv('HOME') ?: getenv('USERPROFILE');
    $dbDir = $homeDir . DIRECTORY_SEPARATOR . '.cold-hot';

    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    return $dbDir . DIRECTORY_SEPARATOR . 'cold-hot.db';
}

/**
 * Инициализирует соединение с базой данных и создаёт таблицы.
 *
 * @return PDO Объект соединения с БД
 */
function initDatabase(): PDO
{
    $dbPath = getDatabasePath();
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    return $pdo;
}

/**
 * Создаёт новую запись игры в базе данных.
 *
 * @param PDO $pdo Соединение с БД
 * @param string $playerName Имя игрока
 * @param string $secretNumber Загаданное число
 * @return int ID созданной игры
 */
function createGame(PDO $pdo, string $playerName, string $secretNumber): int
{
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
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @param string $outcome Результат (угадал/не угадал)
 */
function updateGameOutcome(PDO $pdo, int $gameId, string $outcome): void
{
    $stmt = $pdo->prepare('UPDATE games SET outcome = :outcome WHERE id = :id');
    $stmt->execute([':outcome' => $outcome, ':id' => $gameId]);
}

/**
 * Сохраняет попытку в базу данных.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @param int $attemptNumber Номер попытки
 * @param string $guess Введённое число
 * @param string $hints Подсказки
 */
function saveAttempt(PDO $pdo, int $gameId, int $attemptNumber, string $guess, string $hints): void
{
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
 * Получает список всех игр.
 *
 * @param PDO $pdo Соединение с БД
 * @return array Массив игр
 */
function getAllGames(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM games ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получает игру по ID.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @return array|null Данные игры или null
 */
function getGameById(PDO $pdo, int $gameId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
    $stmt->execute([':id' => $gameId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
}

/**
 * Получает все попытки для игры.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @return array Массив попыток
 */
function getAttemptsByGameId(PDO $pdo, int $gameId): array
{
    $stmt = $pdo->prepare('
        SELECT * FROM attempts
        WHERE game_id = :game_id
        ORDER BY attempt_number ASC
    ');
    $stmt->execute([':game_id' => $gameId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
