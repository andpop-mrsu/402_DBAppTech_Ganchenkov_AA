/**
 * Database module - работа с localStorage (в Task07 будет IndexedDB)
 */

const STORAGE_KEY = 'cold-hot-games';

/**
 * Получает все сохранённые игры.
 * @returns {Array} Массив игр
 */
export function getAllGames() {
    const data = localStorage.getItem(STORAGE_KEY);
    return data ? JSON.parse(data) : [];
}

/**
 * Сохраняет игру.
 * @param {Object} game Данные игры
 * @returns {number} ID созданной игры
 */
export function saveGame(game) {
    const games = getAllGames();
    const id = games.length > 0 ? Math.max(...games.map(g => g.id)) + 1 : 1;
    
    const newGame = {
        id,
        ...game,
        created_at: new Date().toISOString()
    };
    
    games.push(newGame);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(games));
    
    return id;
}

/**
 * Обновляет результат игры.
 * @param {number} gameId ID игры
 * @param {string} outcome Результат
 * @param {Array} attempts Массив попыток
 */
export function updateGame(gameId, outcome, attempts) {
    const games = getAllGames();
    const index = games.findIndex(g => g.id === gameId);
    
    if (index !== -1) {
        games[index].outcome = outcome;
        games[index].attempts = attempts;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(games));
    }
}

/**
 * Получает игру по ID.
 * @param {number} gameId ID игры
 * @returns {Object|null} Данные игры или null
 */
export function getGameById(gameId) {
    const games = getAllGames();
    return games.find(g => g.id === gameId) || null;
}

/**
 * Очищает все сохранённые игры.
 */
export function clearAllGames() {
    localStorage.removeItem(STORAGE_KEY);
}
