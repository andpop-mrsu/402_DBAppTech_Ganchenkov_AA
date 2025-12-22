<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Model;

/**
 * Генерирует случайное трехзначное число без повторяющихся цифр.
 * Первая цифра не может быть нулем.
 *
 * @return string Трехзначное число в виде строки
 */
function generateSecretNumber(): string
{
    $digits = range(1, 9);
    shuffle($digits);
    $firstDigit = $digits[0];

    $remainingDigits = range(0, 9);
    $remainingDigits = array_diff($remainingDigits, [$firstDigit]);
    $remainingDigits = array_values($remainingDigits);
    shuffle($remainingDigits);

    return $firstDigit . $remainingDigits[0] . $remainingDigits[1];
}

/**
 * Проверяет, что ввод является валидным трехзначным числом.
 *
 * @param string $guess Введенная строка
 * @return bool true если строка состоит ровно из трех цифр
 */
function validateGuess(string $guess): bool
{
    return preg_match('/^\d{3}$/', $guess) === 1;
}

/**
 * Генерирует подсказки для каждой цифры попытки.
 *
 * @param string $secret Секретное число
 * @param string $guess Попытка игрока
 * @return array Массив подсказок (несортированный)
 */
function generateHints(string $secret, string $guess): array
{
    $hints = [];

    for ($i = 0; $i < 3; $i++) {
        $guessDigit = $guess[$i];

        if ($guessDigit === $secret[$i]) {
            $hints[] = 'Горячо';
        } elseif (str_contains($secret, $guessDigit)) {
            $hints[] = 'Тепло';
        } else {
            $hints[] = 'Холодно';
        }
    }

    return $hints;
}

/**
 * Сортирует подсказки в алфавитном порядке.
 *
 * @param array $hints Массив подсказок
 * @return array Отсортированный массив подсказок
 */
function sortHints(array $hints): array
{
    $order = ['Горячо' => 1, 'Тепло' => 2, 'Холодно' => 3];

    usort($hints, function ($a, $b) use ($order) {
        return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
    });

    return $hints;
}

/**
 * Проверяет, угадано ли число полностью.
 *
 * @param string $secret Секретное число
 * @param string $guess Попытка игрока
 * @return bool true если числа совпадают
 */
function isCorrectGuess(string $secret, string $guess): bool
{
    return $secret === $guess;
}
