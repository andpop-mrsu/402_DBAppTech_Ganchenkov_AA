<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Controller;

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
use function Ganchenkov\ColdHot\View\showDatabaseNotSupported;
use function Ganchenkov\ColdHot\View\showHelp as viewShowHelp;

/**
 * Точка входа в игру. Парсит CLI-аргументы и запускает соответствующий режим.
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
 * Парсит аргументы командной строки и возвращает режим работы.
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
 * Запускает новую игру: генерирует число, запрашивает имя, управляет циклом игры.
 */
function runNewGame(): void
{
    showWelcome();

    $playerName = '';
    while (strlen($playerName) === 0) {
        $playerName = showPromptName();
        if (strlen($playerName) === 0) {
            showError('Имя не может быть пустым');
        }
    }

    $secretNumber = generateSecretNumber();

    gameLoop($secretNumber);
}

/**
 * Основной цикл игры: ввод попыток, проверка, вывод подсказок.
 *
 * @param string $secretNumber Секретное число
 */
function gameLoop(string $secretNumber): void
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

        if (isCorrectGuess($secretNumber, $guess)) {
            showVictory($attemptNumber);
            break;
        }

        showHints($sortedHints);
    }
}

/**
 * Выводит сообщение о том, что список игр недоступен без БД.
 */
function runListGames(): void
{
    showDatabaseNotSupported();
}

/**
 * Выводит сообщение о том, что повтор игры недоступен без БД.
 *
 * @param int|null $gameId ID игры для воспроизведения
 */
function runReplayGame(?int $gameId): void
{
    showDatabaseNotSupported();
}

/**
 * Выводит справочную информацию.
 */
function showHelp(): void
{
    viewShowHelp();
}
