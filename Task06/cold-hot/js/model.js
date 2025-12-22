/**
 * Model module - игровая логика
 */

/**
 * Генерирует случайное трёхзначное число без повторяющихся цифр.
 * Первая цифра не может быть нулём.
 * @returns {string} Трёхзначное число в виде строки
 */
export function generateSecretNumber() {
    const digits = [1, 2, 3, 4, 5, 6, 7, 8, 9];
    shuffleArray(digits);
    const firstDigit = digits[0];

    const remainingDigits = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9].filter(d => d !== firstDigit);
    shuffleArray(remainingDigits);

    return `${firstDigit}${remainingDigits[0]}${remainingDigits[1]}`;
}

/**
 * Перемешивает массив (Fisher-Yates shuffle)
 * @param {Array} array 
 */
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

/**
 * Проверяет, что ввод является валидным трёхзначным числом.
 * @param {string} guess Введённая строка
 * @returns {boolean} true если строка состоит ровно из трёх цифр
 */
export function validateGuess(guess) {
    return /^\d{3}$/.test(guess);
}

/**
 * Генерирует подсказки для каждой цифры попытки.
 * @param {string} secret Секретное число
 * @param {string} guess Попытка игрока
 * @returns {Array<string>} Массив подсказок (несортированный)
 */
export function generateHints(secret, guess) {
    const hints = [];

    for (let i = 0; i < 3; i++) {
        const guessDigit = guess[i];

        if (guessDigit === secret[i]) {
            hints.push('Горячо');
        } else if (secret.includes(guessDigit)) {
            hints.push('Тепло');
        } else {
            hints.push('Холодно');
        }
    }

    return hints;
}

/**
 * Сортирует подсказки в алфавитном порядке.
 * @param {Array<string>} hints Массив подсказок
 * @returns {Array<string>} Отсортированный массив подсказок
 */
export function sortHints(hints) {
    const order = { 'Горячо': 1, 'Тепло': 2, 'Холодно': 3 };
    return [...hints].sort((a, b) => (order[a] || 99) - (order[b] || 99));
}

/**
 * Проверяет, угадано ли число полностью.
 * @param {string} secret Секретное число
 * @param {string} guess Попытка игрока
 * @returns {boolean} true если числа совпадают
 */
export function isCorrectGuess(secret, guess) {
    return secret === guess;
}
