/**
 * Controller module - управление игрой
 */

import { generateSecretNumber, validateGuess, generateHints, sortHints, isCorrectGuess } from './model.js';
import { createGame, updateGameOutcome, saveAttempt, getAllGames, getGameById, getAttemptsByGameId, deleteGame } from './database.js';
import { showHints, addToHistory, showVictory, updateAttemptsCount, showError, clearInput, resetGameUI, showNameForm } from './view.js';

// Состояние текущей игры
let currentGame = {
    id: null,
    playerName: '',
    secretNumber: '',
    attemptCount: 0
};

/**
 * Начинает новую игру.
 * @param {string} playerName Имя игрока
 */
export async function startNewGame(playerName) {
    if (!playerName || playerName.trim() === '') {
        showError('Введите ваше имя');
        return;
    }

    const secretNumber = generateSecretNumber();
    
    currentGame = {
        id: null,
        playerName: playerName.trim(),
        secretNumber,
        attemptCount: 0
    };

    // Сохраняем игру в IndexedDB
    currentGame.id = await createGame({
        player_name: currentGame.playerName,
        secret_number: secretNumber
    });

    resetGameUI(currentGame.playerName);
}

/**
 * Обрабатывает попытку угадать число.
 * @param {string} guess Введённое число
 */
export async function makeGuess(guess) {
    if (!validateGuess(guess)) {
        showError('Введите корректное трёхзначное число');
        return;
    }

    currentGame.attemptCount++;
    updateAttemptsCount(currentGame.attemptCount);

    const hints = generateHints(currentGame.secretNumber, guess);
    const sortedHints = sortHints(hints);
    const hintsString = sortedHints.join(' ');

    // Сохраняем попытку в IndexedDB
    await saveAttempt({
        game_id: currentGame.id,
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
        await updateGameOutcome(currentGame.id, 'угадал');
        showVictory(currentGame.secretNumber, currentGame.attemptCount);
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
export async function displayGamesList() {
    const games = await getAllGames();
    const container = document.getElementById('games-list');

    if (games.length === 0) {
        container.innerHTML = '<p class="no-games">Сохранённых партий пока нет</p>';
        return;
    }

    container.innerHTML = games.map(game => {
        const date = new Date(game.created_at).toLocaleString('ru-RU');
        const outcome = game.outcome || 'в процессе';

        return `
            <div class="game-card" data-game-id="${game.id}">
                <div class="game-card-header">
                    <strong>#${game.id}</strong>
                    <span>${date}</span>
                </div>
                <div class="game-card-details">
                    <div>Игрок: ${game.player_name}</div>
                    <div>Число: ${game.secret_number}</div>
                    <div>Результат: ${outcome}</div>
                </div>
                <div class="game-card-actions">
                    <button class="replay-btn" onclick="window.showReplay(${game.id})">Показать ходы</button>
                    <button class="delete-btn" onclick="window.deleteGameById(${game.id})">Удалить</button>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Показывает воспроизведение партии.
 * @param {number} gameId ID игры
 */
export async function showReplay(gameId) {
    const game = await getGameById(gameId);
    if (!game) {
        showError('Игра не найдена');
        return;
    }

    const attempts = await getAttemptsByGameId(gameId);
    
    // Показываем модальное окно с воспроизведением
    const modal = document.getElementById('replay-modal');
    const content = document.getElementById('replay-content');
    
    let html = `
        <h3>Партия #${game.id}</h3>
        <p><strong>Игрок:</strong> ${game.player_name}</p>
        <p><strong>Дата:</strong> ${new Date(game.created_at).toLocaleString('ru-RU')}</p>
        <p><strong>Загаданное число:</strong> ${game.secret_number}</p>
        <p><strong>Результат:</strong> ${game.outcome || 'в процессе'}</p>
        <hr>
        <h4>Попытки:</h4>
    `;

    if (attempts.length === 0) {
        html += '<p>Попыток не было</p>';
    } else {
        html += '<div class="replay-attempts">';
        attempts.forEach(attempt => {
            html += `<div class="replay-attempt">${attempt.attempt_number}. ${attempt.guess} → ${attempt.hints}</div>`;
        });
        html += '</div>';
    }

    content.innerHTML = html;
    modal.classList.remove('hidden');
}

/**
 * Закрывает модальное окно.
 */
export function closeModal() {
    document.getElementById('replay-modal').classList.add('hidden');
}

/**
 * Удаляет игру по ID.
 * @param {number} gameId ID игры
 */
export async function deleteGameById(gameId) {
    if (confirm('Удалить эту партию?')) {
        await deleteGame(gameId);
        await displayGamesList();
    }
}

// Делаем функции доступными глобально
window.showReplay = showReplay;
window.deleteGameById = deleteGameById;
window.closeModal = closeModal;
