/**
 * Controller module - управление игрой
 */

import { generateSecretNumber, validateGuess, generateHints, sortHints, isCorrectGuess } from './model.js';
import { saveGame, updateGame, getAllGames, getGameById } from './database.js';
import { showHints, addToHistory, showVictory, updateAttemptsCount, showError, clearInput, resetGameUI, showNameForm } from './view.js';

// Состояние текущей игры
let currentGame = {
    id: null,
    playerName: '',
    secretNumber: '',
    attempts: [],
    attemptCount: 0
};

/**
 * Начинает новую игру.
 * @param {string} playerName Имя игрока
 */
export function startNewGame(playerName) {
    if (!playerName || playerName.trim() === '') {
        showError('Введите ваше имя');
        return;
    }

    const secretNumber = generateSecretNumber();
    
    currentGame = {
        id: null,
        playerName: playerName.trim(),
        secretNumber,
        attempts: [],
        attemptCount: 0
    };

    // Сохраняем игру в БД
    currentGame.id = saveGame({
        player_name: currentGame.playerName,
        secret_number: secretNumber,
        outcome: null,
        attempts: []
    });

    resetGameUI(currentGame.playerName);
}

/**
 * Обрабатывает попытку угадать число.
 * @param {string} guess Введённое число
 */
export function makeGuess(guess) {
    if (!validateGuess(guess)) {
        showError('Введите корректное трёхзначное число');
        return;
    }

    currentGame.attemptCount++;
    updateAttemptsCount(currentGame.attemptCount);

    const hints = generateHints(currentGame.secretNumber, guess);
    const sortedHints = sortHints(hints);
    const hintsString = sortedHints.join(' ');

    // Сохраняем попытку
    currentGame.attempts.push({
        attempt_number: currentGame.attemptCount,
        guess,
        hints: hintsString
    });

    // Показываем подсказки
    showHints(sortedHints);
    addToHistory(currentGame.attemptCount, guess, hintsString);
    clearInput();

    // Проверяем победу
    if (isCorrectGuess(currentGame.secretNumber, guess)) {
        updateGame(currentGame.id, 'угадал', currentGame.attempts);
        showVictory(currentGame.secretNumber, currentGame.attemptCount);
    } else {
        updateGame(currentGame.id, null, currentGame.attempts);
    }
}

/**
 * Сбрасывает игру для начала новой.
 */
export function resetGame() {
    showNameForm();
}

/**
 * Отображает список сохранённых игр.
 */
export function displayGamesList() {
    const games = getAllGames();
    const container = document.getElementById('games-list');

    if (games.length === 0) {
        container.innerHTML = '<p class="no-games">Сохранённых партий пока нет</p>';
        return;
    }

    // Сортируем по дате (новые сверху)
    const sortedGames = [...games].sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
    );

    container.innerHTML = sortedGames.map(game => {
        const date = new Date(game.created_at).toLocaleString('ru-RU');
        const outcome = game.outcome || 'в процессе';
        const attemptsCount = game.attempts ? game.attempts.length : 0;

        return `
            <div class="game-card">
                <div class="game-card-header">
                    <strong>#${game.id}</strong>
                    <span>${date}</span>
                </div>
                <div class="game-card-details">
                    <div>Игрок: ${game.player_name}</div>
                    <div>Число: ${game.secret_number}</div>
                    <div>Попыток: ${attemptsCount}</div>
                    <div>Результат: ${outcome}</div>
                </div>
                ${game.attempts && game.attempts.length > 0 ? 
                    `<button class="replay-btn" onclick="window.showReplay(${game.id})">Показать ходы</button>` : ''}
            </div>
        `;
    }).join('');
}

/**
 * Показывает воспроизведение партии.
 * @param {number} gameId ID игры
 */
export function showReplay(gameId) {
    const game = getGameById(gameId);
    if (!game || !game.attempts) return;

    let replayText = `Партия #${game.id}\n`;
    replayText += `Игрок: ${game.player_name}\n`;
    replayText += `Загаданное число: ${game.secret_number}\n\n`;
    replayText += `Попытки:\n`;
    
    game.attempts.forEach(attempt => {
        replayText += `${attempt.attempt_number}. ${attempt.guess} → ${attempt.hints}\n`;
    });

    alert(replayText);
}

// Делаем функцию доступной глобально для onclick
window.showReplay = showReplay;
