/**
 * App module - точка входа и инициализация
 */

import { startNewGame, makeGuess, resetGame, displayGamesList } from './controller.js';

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initGameControls();
});

/**
 * Инициализирует переключение вкладок.
 */
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;

            // Убираем активный класс со всех
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Добавляем активный класс выбранным
            tab.classList.add('active');
            document.getElementById(targetId).classList.add('active');

            // Обновляем список игр при переходе на вкладку истории
            if (targetId === 'history') {
                displayGamesList();
            }
        });
    });
}

/**
 * Инициализирует элементы управления игрой.
 */
function initGameControls() {
    // Кнопка начала игры
    const startBtn = document.getElementById('start-btn');
    const playerNameInput = document.getElementById('player-name');

    startBtn.addEventListener('click', () => {
        startNewGame(playerNameInput.value);
    });

    playerNameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            startNewGame(playerNameInput.value);
        }
    });

    // Кнопка проверки числа
    const guessBtn = document.getElementById('guess-btn');
    const guessInput = document.getElementById('guess-input');

    guessBtn.addEventListener('click', () => {
        makeGuess(guessInput.value);
    });

    guessInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            makeGuess(guessInput.value);
        }
    });

    // Разрешаем только цифры в поле ввода
    guessInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
    });

    // Кнопка новой игры после победы
    const newGameBtn = document.getElementById('new-game-btn');
    newGameBtn.addEventListener('click', () => {
        resetGame();
    });
}
