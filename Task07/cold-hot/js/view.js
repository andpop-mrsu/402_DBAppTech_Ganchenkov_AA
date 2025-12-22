/**
 * View module - отображение интерфейса
 */

/**
 * Показывает подсказки после попытки.
 * @param {Array<string>} hints Массив подсказок
 */
export function showHints(hints) {
    const container = document.getElementById('hints');
    container.innerHTML = hints.map(hint => {
        let className = 'hint ';
        if (hint === 'Горячо') className += 'hot';
        else if (hint === 'Тепло') className += 'warm';
        else className += 'cold';
        
        let emoji = hint === 'Горячо' ? '🔥' : hint === 'Тепло' ? '🌡️' : '❄️';
        
        return `<span class="${className}">${emoji} ${hint}</span>`;
    }).join('');
}

/**
 * Добавляет попытку в историю текущей игры.
 * @param {number} attemptNumber Номер попытки
 * @param {string} guess Введённое число
 * @param {string} hintsStr Подсказки строкой
 */
export function addToHistory(attemptNumber, guess, hintsStr) {
    const container = document.getElementById('history-list');
    const item = document.createElement('div');
    item.className = 'history-item';
    item.innerHTML = `
        <span>#${attemptNumber}: ${guess}</span>
        <span>${hintsStr}</span>
    `;
    container.insertBefore(item, container.firstChild);
}

/**
 * Показывает экран победы.
 * @param {string} secretNumber Загаданное число
 * @param {number} attempts Количество попыток
 */
export function showVictory(secretNumber, attempts) {
    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('victory').classList.remove('hidden');
    document.getElementById('secret-reveal').textContent = secretNumber;
    document.getElementById('final-attempts').textContent = attempts;
}

/**
 * Обновляет счётчик попыток.
 * @param {number} count Количество попыток
 */
export function updateAttemptsCount(count) {
    document.getElementById('attempts-count').textContent = count;
}

/**
 * Показывает сообщение об ошибке.
 * @param {string} message Текст ошибки
 */
export function showError(message) {
    alert(message);
}

/**
 * Очищает поле ввода.
 */
export function clearInput() {
    const input = document.getElementById('guess-input');
    input.value = '';
    input.focus();
}

/**
 * Сбрасывает игровой интерфейс для новой игры.
 * @param {string} playerName Имя игрока
 */
export function resetGameUI(playerName) {
    document.getElementById('name-form').classList.add('hidden');
    document.getElementById('game-area').classList.remove('hidden');
    document.getElementById('victory').classList.add('hidden');
    document.getElementById('current-player').textContent = playerName;
    document.getElementById('attempts-count').textContent = '0';
    document.getElementById('hints').innerHTML = '';
    document.getElementById('history-list').innerHTML = '';
    document.getElementById('guess-input').value = '';
    document.getElementById('guess-input').focus();
}

/**
 * Показывает форму ввода имени.
 */
export function showNameForm() {
    document.getElementById('name-form').classList.remove('hidden');
    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('victory').classList.add('hidden');
    document.getElementById('player-name').value = '';
    document.getElementById('player-name').focus();
}
