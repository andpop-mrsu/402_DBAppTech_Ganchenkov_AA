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
 * Читает ввод из консоли с поддержкой кириллицы в Windows.
 *
 * @return string Введённая строка
 */
function readInput(): string
{
    $input = fgets(STDIN);

    if ($input === false || $input === '') {
        return '';
    }

    $input = rtrim($input, "\r\n");

    if ($input !== '') {
        return $input;
    }

    return '';
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
 * Выводит сообщение о том, что база данных не поддерживается.
 */
function showDatabaseNotSupported(): void
{
    line('');
    line('Внимание: В текущей версии игра не сохраняется в базе данных.');
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
