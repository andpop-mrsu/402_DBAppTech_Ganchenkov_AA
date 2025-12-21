/**
 * Database module - работа с IndexedDB через библиотеку idb
 */

import { openDB } from 'https://cdn.jsdelivr.net/npm/idb@7/+esm';

const DB_NAME = 'cold-hot-db';
const DB_VERSION = 1;

let dbPromise = null;

/**
 * Инициализирует базу данных IndexedDB.
 * @returns {Promise} Promise с объектом базы данных
 */
async function getDB() {
    if (!dbPromise) {
        dbPromise = openDB(DB_NAME, DB_VERSION, {
            upgrade(db) {
                // Создаём хранилище для игр
                if (!db.objectStoreNames.contains('games')) {
                    const gamesStore = db.createObjectStore('games', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    gamesStore.createIndex('created_at', 'created_at');
                    gamesStore.createIndex('player_name', 'player_name');
                }

                // Создаём хранилище для попыток
                if (!db.objectStoreNames.contains('attempts')) {
                    const attemptsStore = db.createObjectStore('attempts', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    attemptsStore.createIndex('game_id', 'game_id');
                }
            }
        });
    }
    return dbPromise;
}

/**
 * Создаёт новую игру.
 * @param {Object} gameData Данные игры
 * @returns {Promise<number>} ID созданной игры
 */
export async function createGame(gameData) {
    const db = await getDB();
    const game = {
        player_name: gameData.player_name,
        secret_number: gameData.secret_number,
        outcome: null,
        created_at: new Date().toISOString()
    };
    return await db.add('games', game);
}

/**
 * Обновляет результат игры.
 * @param {number} gameId ID игры
 * @param {string} outcome Результат
 */
export async function updateGameOutcome(gameId, outcome) {
    const db = await getDB();
    const game = await db.get('games', gameId);
    if (game) {
        game.outcome = outcome;
        await db.put('games', game);
    }
}


/**
 * Сохраняет попытку.
 * @param {Object} attemptData Данные попытки
 */
export async function saveAttempt(attemptData) {
    const db = await getDB();
    await db.add('attempts', attemptData);
}

/**
 * Получает все игры.
 * @returns {Promise<Array>} Массив игр
 */
export async function getAllGames() {
    const db = await getDB();
    const games = await db.getAll('games');
    // Сортируем по дате (новые сверху)
    return games.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
}

/**
 * Получает игру по ID.
 * @param {number} gameId ID игры
 * @returns {Promise<Object|null>} Данные игры или null
 */
export async function getGameById(gameId) {
    const db = await getDB();
    return await db.get('games', gameId);
}

/**
 * Получает все попытки для игры.
 * @param {number} gameId ID игры
 * @returns {Promise<Array>} Массив попыток
 */
export async function getAttemptsByGameId(gameId) {
    const db = await getDB();
    const allAttempts = await db.getAllFromIndex('attempts', 'game_id', gameId);
    return allAttempts.sort((a, b) => a.attempt_number - b.attempt_number);
}

/**
 * Удаляет игру и все её попытки.
 * @param {number} gameId ID игры
 */
export async function deleteGame(gameId) {
    const db = await getDB();
    
    // Удаляем попытки
    const attempts = await getAttemptsByGameId(gameId);
    for (const attempt of attempts) {
        await db.delete('attempts', attempt.id);
    }
    
    // Удаляем игру
    await db.delete('games', gameId);
}

/**
 * Очищает все данные.
 */
export async function clearAllData() {
    const db = await getDB();
    await db.clear('games');
    await db.clear('attempts');
}
