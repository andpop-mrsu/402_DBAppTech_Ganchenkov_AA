<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Controller;

use PDO;

use function Ganchenkov\ColdHot\Model\generateSecretNumber;
use function Ganchenkov\ColdHot\Model\validateGuess;
use function Ganchenkov\ColdHot\Model\generateHints;
use function Ganchenkov\ColdHot\Model\sortHints;
use function Ganchenkov\ColdHot\Model\isCorrectGuess;
use function Ganchenkov\ColdHot\View\showWelcome;
use function Ganchenkov\ColdHot\View\showPromptName;
use function Ganchenkov\ColdHot\View\showPromptGuess;
use function Ganchenkov\ColdHot\View\showHints;
use function Ganchenkov\ColdHot\View\showVictory;
use function Ganchenkov\ColdHot\View\showError;
use function Ganchenkov\ColdHot\View\showGamesList;
use function Ganchenkov\ColdHot\View\showReplay;
use function Ganchenkov\ColdHot\View\showNoGames;
use function Ganchenkov\ColdHot\View\showGameNotFound;
use function Ganchenkov\ColdHot\View\showHelp as viewShowHelp;
use function Ganchenkov\ColdHot\Database\initDatabase;
use function Ganchenkov\ColdHot\Database\createGame;
use function Ganchenkov\ColdHot\Database\updateGameOutcome;
use function Ganchenkov\ColdHot\Database\saveAttempt;
use function Ganchenkov\ColdHot\Database\getAllGames;
use function Ganchenkov\ColdHot\Database\getGameById;
use function Ganchenkov\ColdHot\Database\getAttemptsByGameId;

/**
 * Точка входа в игру.
 */
function startGame(): void
{
    global $argv;

    $args = parseArguments($argv);

    switch ($args['mode']) {
        case 'new':
            runNewGame();
            break;
        case 'list':
            runListGames();
            break;
        case 'replay':
            runReplayGame($args['game_id']);
            break;
        case 'help':
        default:
            showHelp();
            break;
    }
}

/**
 * Парсит аргументы командной строки.
 *
 * @param array $argv Аргументы командной строки
 * @return array ['mode' => string, 'game_id' => int|null]
 */
function parseArguments(array $argv): array
{
    $result = [
        'mode' => 'new',
        'game_id' => null
    ];

    $argc = count($argv);

    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];

        switch ($arg) {
            case '-n':
            case '--new':
                $result['mode'] = 'new';
                return $result;

            case '-l':
            case '--list':
                $result['mode'] = 'list';
                return $result;

            case '-r':
            case '--replay':
                $result['mode'] = 'replay';
                if (isset($argv[$i + 1]) && is_numeric($argv[$i + 1])) {
                    $result['game_id'] = (int) $argv[$i + 1];
                }
                return $result;

            case '-h':
            case '--help':
                $result['mode'] = 'help';
                return $result;
        }
    }

    return $result;
}

/**
 * Запускает новую игру.
 */
function runNewGame(): void
{
    $pdo = initDatabase();

    showWelcome();

    $playerName = '';
    while (strlen($playerName) === 0) {
        $playerName = showPromptName();
        if (strlen($playerName) === 0) {
            showError('Имя не может быть пустым');
        }
    }

    $secretNumber = generateSecretNumber();
    $gameId = createGame($pdo, $playerName, $secretNumber);

    gameLoop($pdo, $gameId, $secretNumber);
}

/**
 * Основной цикл игры.
 *
 * @param PDO $pdo Соединение с БД
 * @param int $gameId ID текущей игры
 * @param string $secretNumber Секретное число
 */
function gameLoop(PDO $pdo, int $gameId, string $secretNumber): void
{
    $attemptNumber = 0;

    while (true) {
        $guess = showPromptGuess();

        if (!validateGuess($guess)) {
            showError('Введите корректное трехзначное число');
            continue;
        }

        $attemptNumber++;

        $hints = generateHints($secretNumber, $guess);
        $sortedHints = sortHints($hints);
        $hintsString = implode(' ', $sortedHints);

        saveAttempt($pdo, $gameId, $attemptNumber, $guess, $hintsString);

        if (isCorrectGuess($secretNumber, $guess)) {
            updateGameOutcome($pdo, $gameId, 'угадал');
            showVictory($attemptNumber);
            break;
        }

        showHints($sortedHints);
    }
}

/**
 * Выводит список всех сохраненных партий.
 */
function runListGames(): void
{
    $pdo = initDatabase();
    $games = getAllGames($pdo);

    if (empty($games)) {
        showNoGames();
    } else {
        showGamesList($games);
    }
}

/**
 * Воспроизводит сохраненную партию.
 *
 * @param int|null $gameId ID игры
 */
function runReplayGame(?int $gameId): void
{
    if ($gameId === null) {
        showError('Не указан ID игры. Используйте: --replay <ID>');
        return;
    }

    $pdo = initDatabase();
    $game = getGameById($pdo, $gameId);

    if ($game === null) {
        showGameNotFound($gameId);
        return;
    }

    $attempts = getAttemptsByGameId($pdo, $gameId);
    showReplay($game, $attempts);
}

/**
 * Выводит справочную информацию.
 */
function showHelp(): void
{
    viewShowHelp();
}
