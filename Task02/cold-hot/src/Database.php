<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Database;

use PDO;
use PDOException;

/**
 * Путь к файлу базы данных SQLite.
 */
const DB_PATH = __DIR__ . '/../cold-hot.db';

/**
 * Инициализирует соединение с БД и создает таблицы при необходимости.
 *
 * @return PDO Объект соединения с базой данных
 */
function initDatabase(): PDO
{
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Создание таблицы игровых сессий
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            secret_number TEXT NOT NULL,
            outcome TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Создание таблицы попыток
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
 * Создает новую запись игры и возвращает её ID.
 *
 * @param PDO $pdo Соединение с БД
 * @param string $playerName Имя игрока
 * @param string $secretNumber Секретное число
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
 * Обновляет исход игры.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @param string $outcome Исход игры ('угадал' или null)
 */
function updateGameOutcome(PDO $pdo, int $gameId, string $outcome): void
{
    $stmt = $pdo->prepare('
        UPDATE games SET outcome = :outcome WHERE id = :id
    ');
    $stmt->execute([
        ':outcome' => $outcome,
        ':id' => $gameId
    ]);
}

/**
 * Возвращает все сохраненные игры.
 *
 * @param PDO $pdo Соединение с БД
 * @return array Массив всех игр
 */
function getAllGames(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM games ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

/**
 * Возвращает игру по ID или null.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @return array|null Данные игры или null если не найдена
 */
function getGameById(PDO $pdo, int $gameId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
    $stmt->execute([':id' => $gameId]);
    $result = $stmt->fetch();
    
    return $result !== false ? $result : null;
}


/**
 * Сохраняет попытку в БД.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID игры
 * @param int $attemptNumber Номер попытки
 * @param string $guess Введенное число
 * @param string $hints Подсказки (строка)
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
 * Возвращает все попытки для указанной игры.
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
    return $stmt->fetchAll();
}
