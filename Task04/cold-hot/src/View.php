<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\View;

use function cli\line;

/**
 * Выводит приветственное сообщение и правила игры.
 */
function showWelcome(): void
{
    line('');
    line('===========================================');
    line('   Добро пожаловать в игру "Холодно-горячо"!');
    line('===========================================');
    line('');
    line('Правила игры:');
    line('  - Компьютер загадал трехзначное число без повторяющихся цифр');
    line('  - Первая цифра не может быть нулем');
    line('  - После каждой попытки вы получите подсказки:');
    line('    * Горячо - цифра на своем месте');
    line('    * Тепло  - цифра есть, но не на своем месте');
    line('    * Холодно - такой цифры нет в числе');
    line('  - Подсказки выводятся в алфавитном порядке');
    line('');
}

/**
 * Читает ввод из консоли.
 *
 * @return string Введённая строка
 */
function readInput(): string
{
    $input = fgets(STDIN);

    if ($input === false || $input === '') {
        return '';
    }

    return rtrim($input, "\r\n");
}

/**
 * Запрашивает имя игрока.
 *
 * @return string Введенное имя игрока
 */
function showPromptName(): string
{
    echo 'Введите ваше имя: ';
    return readInput();
}

/**
 * Запрашивает попытку угадать число.
 *
 * @return string Введенная попытка
 */
function showPromptGuess(): string
{
    echo 'Введите трехзначное число: ';
    return readInput();
}

/**
 * Выводит подсказки после попытки.
 *
 * @param array $hints Массив подсказок
 */
function showHints(array $hints): void
{
    line('Подсказки: %s', implode(' ', $hints));
}

/**
 * Выводит сообщение о победе.
 *
 * @param int $attempts Количество попыток
 */
function showVictory(int $attempts): void
{
    line('');
    line('Поздравляем! Вы угадали число за %d попыток!', $attempts);
    line('');
}

/**
 * Выводит сообщение об ошибке.
 *
 * @param string $message Текст ошибки
 */
function showError(string $message): void
{
    line('Ошибка: %s', $message);
}

/**
 * Выводит список сохраненных партий.
 *
 * @param array $games Массив игр из БД
 */
function showGamesList(array $games): void
{
    line('');
    line('=== Список сохраненных партий ===');
    line('');
    line('%-4s | %-20s | %-15s | %-6s | %-10s', 'ID', 'Дата', 'Игрок', 'Число', 'Результат');
    line(str_repeat('-', 65));

    foreach ($games as $game) {
        $outcome = $game['outcome'] ?? 'в процессе';
        line(
            '%-4s | %-20s | %-15s | %-6s | %-10s',
            $game['id'],
            $game['created_at'],
            $game['player_name'],
            $game['secret_number'],
            $outcome
        );
    }
    line('');
}

/**
 * Выводит воспроизведение партии.
 *
 * @param array $game Данные игры
 * @param array $attempts Массив попыток
 */
function showReplay(array $game, array $attempts): void
{
    line('');
    line('=== Воспроизведение партии #%d ===', $game['id']);
    line('');
    line('Игрок: %s', $game['player_name']);
    line('Дата: %s', $game['created_at']);
    line('Загаданное число: %s', $game['secret_number']);
    line('Результат: %s', $game['outcome'] ?? 'в процессе');
    line('');

    if (empty($attempts)) {
        line('Попыток не было.');
    } else {
        line('Попытки:');
        foreach ($attempts as $attempt) {
            line(
                '  %d. %s -> %s',
                $attempt['attempt_number'],
                $attempt['guess'],
                $attempt['hints']
            );
        }
    }
    line('');
}

/**
 * Выводит сообщение об отсутствии сохраненных игр.
 */
function showNoGames(): void
{
    line('');
    line('Сохраненных партий пока нет.');
    line('Запустите новую игру с параметром --new или -n');
    line('');
}

/**
 * Выводит сообщение о ненайденной игре.
 *
 * @param int $id ID игры
 */
function showGameNotFound(int $id): void
{
    line('');
    line('Игра с ID %d не найдена.', $id);
    line('');
}

/**
 * Выводит справочную информацию.
 */
function showHelp(): void
{
    line('');
    line('Игра "Холодно-горячо" (cold-hot)');
    line('================================');
    line('');
    line('Описание:');
    line('  Консольная игра, в которой нужно угадать трехзначное число');
    line('  без повторяющихся цифр. После каждой попытки выводятся подсказки.');
    line('');
    line('Использование:');
    line('  cold-hot [параметры]');
    line('');
    line('Параметры:');
    line('  -n, --new          Начать новую игру');
    line('  -l, --list         Показать список сохраненных партий');
    line('  -r, --replay <ID>  Воспроизвести партию по ID');
    line('  -h, --help         Показать эту справку');
    line('');
    line('Примеры:');
    line('  cold-hot --new');
    line('  cold-hot -l');
    line('  cold-hot --replay 5');
    line('');
}
