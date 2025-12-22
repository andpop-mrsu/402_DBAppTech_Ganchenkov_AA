<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Database;

use RedBeanPHP\R;

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
 * Инициализирует соединение с базой данных через RedBeanPHP.
 */
function initDatabase(): void
{
    if (!R::testConnection()) {
        $dbPath = getDatabasePath();
        R::setup('sqlite:' . $dbPath);
        R::freeze(false);
    }
}

/**
 * Создаёт новую запись игры в базе данных.
 *
 * @param string $playerName Имя игрока
 * @param string $secretNumber Загаданное число
 * @return int ID созданной игры
 */
function createGame(string $playerName, string $secretNumber): int
{
    $game = R::dispense('game');
    $game->player_name = $playerName;
    $game->secret_number = $secretNumber;
    $game->outcome = null;
    $game->created_at = date('Y-m-d H:i:s');

    return (int) R::store($game);
}

/**
 * Обновляет результат игры.
 *
 * @param int $gameId ID игры
 * @param string $outcome Результат (угадал/не угадал)
 */
function updateGameOutcome(int $gameId, string $outcome): void
{
    $game = R::load('game', $gameId);
    if ($game->id) {
        $game->outcome = $outcome;
        R::store($game);
    }
}


/**
 * Сохраняет попытку в базу данных.
 *
 * @param int $gameId ID игры
 * @param int $attemptNumber Номер попытки
 * @param string $guess Введённое число
 * @param string $hints Подсказки
 */
function saveAttempt(int $gameId, int $attemptNumber, string $guess, string $hints): void
{
    $attempt = R::dispense('attempt');
    $attempt->game_id = $gameId;
    $attempt->attempt_number = $attemptNumber;
    $attempt->guess = $guess;
    $attempt->hints = $hints;

    R::store($attempt);
}

/**
 * Получает список всех игр.
 *
 * @return array Массив игр
 */
function getAllGames(): array
{
    $games = R::findAll('game', ' ORDER BY created_at DESC ');
    $result = [];

    foreach ($games as $game) {
        $result[] = [
            'id' => $game->id,
            'player_name' => $game->player_name,
            'secret_number' => $game->secret_number,
            'outcome' => $game->outcome,
            'created_at' => $game->created_at
        ];
    }

    return $result;
}

/**
 * Получает игру по ID.
 *
 * @param int $gameId ID игры
 * @return array|null Данные игры или null
 */
function getGameById(int $gameId): ?array
{
    $game = R::load('game', $gameId);

    if (!$game->id) {
        return null;
    }

    return [
        'id' => $game->id,
        'player_name' => $game->player_name,
        'secret_number' => $game->secret_number,
        'outcome' => $game->outcome,
        'created_at' => $game->created_at
    ];
}

/**
 * Получает все попытки для игры.
 *
 * @param int $gameId ID игры
 * @return array Массив попыток
 */
function getAttemptsByGameId(int $gameId): array
{
    $attempts = R::find('attempt', ' game_id = ? ORDER BY attempt_number ASC ', [$gameId]);
    $result = [];

    foreach ($attempts as $attempt) {
        $result[] = [
            'id' => $attempt->id,
            'game_id' => $attempt->game_id,
            'attempt_number' => $attempt->attempt_number,
            'guess' => $attempt->guess,
            'hints' => $attempt->hints
        ];
    }

    return $result;
}
