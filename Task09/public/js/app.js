/**
 * Cold-Hot Game SPA
 * Frontend с REST API (Slim Framework)
 */

const API_BASE = '';

// Состояние игры
let currentGame = {
    id: null,
    playerName: '',
    secretNumber: '',
    attemptCount: 0
};

// ==================== API Functions ====================

async function apiGetGames() {
    const response = await fetch(`${API_BASE}/games`);
    return await response.json();
}

async function apiGetGame(id) {
    const response = await fetch(`${API_BASE}/games/${id}`);
    return await response.json();
}

async function apiCreateGame(playerName, secretNumber) {
    const response = await fetch(`${API_BASE}/games`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ player_name: playerName, secret_number: secretNumber })
    });
    return await response.json();
}

async function apiSaveStep(gameId, attemptNumber, guess, hints, outcome = null) {
    const body = { attempt_number: attemptNumber, guess, hints };
    if (outcome) body.outcome = outcome;
    
    const response = await fetch(`${API_BASE}/step/${gameId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    return await response.json();
}

// ==================== Game Logic ====================

function generateSecretNumber() {
    const digits = [1, 2, 3, 4, 5, 6, 7, 8, 9];
    shuffleArray(digits);
    const first = digits[0];
    
    const remaining = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9].filter(d => d !== first);
    shuffleArray(remaining);
    
    return `${first}${remaining[0]}${remaining[1]}`;
}

function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

function validateGuess(guess) {
    return /^\d{3}$/.test(guess);
}

function generateHints(secret, guess) {
    const hints = [];
    for (let i = 0; i < 3; i++) {
        if (guess[i] === secret[i]) {
            hints.push('Горячо');
        } else if (secret.includes(guess[i])) {
            hints.push('Тепло');
        } else {
            hints.push('Холодно');
        }
    }
    return hints;
}

function sortHints(hints) {
    const order = { 'Горячо': 1, 'Тепло': 2, 'Холодно': 3 };
    return [...hints].sort((a, b) => order[a] - order[b]);
}

// ==================== UI Functions ====================

async function startNewGame(playerName) {
    if (!playerName || playerName.trim() === '') {
        alert('Введите ваше имя');
        return;
    }

    const secretNumber = generateSecretNumber();
    
    // Создаём игру на сервере
    const result = await apiCreateGame(playerName.trim(), secretNumber);
    
    currentGame = {
        id: result.id,
        playerName: playerName.trim(),
        secretNumber,
        attemptCount: 0
    };

    // Обновляем UI
    document.getElementById('name-form').classList.add('hidden');
    document.getElementById('game-area').classList.remove('hidden');
    document.getElementById('victory').classList.add('hidden');
    document.getElementById('current-player').textContent = currentGame.playerName;
    document.getElementById('attempts-count').textContent = '0';
    document.getElementById('hints').innerHTML = '';
    document.getElementById('history-list').innerHTML = '';
    document.getElementById('guess-input').value = '';
    document.getElementById('guess-input').focus();
}

async function makeGuess(guess) {
    if (!validateGuess(guess)) {
        alert('Введите корректное трёхзначное число');
        return;
    }

    currentGame.attemptCount++;
    document.getElementById('attempts-count').textContent = currentGame.attemptCount;

    const hints = generateHints(currentGame.secretNumber, guess);
    const sortedHints = sortHints(hints);
    const hintsString = sortedHints.join(' ');

    // Проверяем победу
    const isWin = guess === currentGame.secretNumber;
    
    // Сохраняем на сервер
    await apiSaveStep(
        currentGame.id,
        currentGame.attemptCount,
        guess,
        hintsString,
        isWin ? 'угадал' : null
    );

    // Показываем подсказки
    showHints(sortedHints);
    addToHistory(currentGame.attemptCount, guess, hintsString);
    
    document.getElementById('guess-input').value = '';
    document.getElementById('guess-input').focus();

    if (isWin) {
        showVictory();
    }
}

function showHints(hints) {
    const container = document.getElementById('hints');
    container.innerHTML = hints.map(hint => {
        let className = 'hint ';
        let emoji = '';
        if (hint === 'Горячо') { className += 'hot'; emoji = '🔥'; }
        else if (hint === 'Тепло') { className += 'warm'; emoji = '🌡️'; }
        else { className += 'cold'; emoji = '❄️'; }
        return `<span class="${className}">${emoji} ${hint}</span>`;
    }).join('');
}

function addToHistory(num, guess, hints) {
    const container = document.getElementById('history-list');
    const item = document.createElement('div');
    item.className = 'history-item';
    item.innerHTML = `<span>#${num}: ${guess}</span><span>${hints}</span>`;
    container.insertBefore(item, container.firstChild);
}

function showVictory() {
    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('victory').classList.remove('hidden');
    document.getElementById('secret-reveal').textContent = currentGame.secretNumber;
    document.getElementById('final-attempts').textContent = currentGame.attemptCount;
}

function resetGame() {
    document.getElementById('name-form').classList.remove('hidden');
    document.getElementById('game-area').classList.add('hidden');
    document.getElementById('victory').classList.add('hidden');
    document.getElementById('player-name').value = '';
    document.getElementById('player-name').focus();
}

async function displayGamesList() {
    const container = document.getElementById('games-list');
    
    try {
        const games = await apiGetGames();
        
        if (games.length === 0) {
            container.innerHTML = '<p class="no-games">Сохранённых партий пока нет</p>';
            return;
        }

        container.innerHTML = games.map(game => {
            const date = new Date(game.created_at).toLocaleString('ru-RU');
            const outcome = game.outcome || 'в процессе';
            return `
                <div class="game-card">
                    <div class="game-card-header">
                        <strong>#${game.id}</strong>
                        <span>${date}</span>
                    </div>
                    <div class="game-card-details">
                        <div>Игрок: ${game.player_name}</div>
                        <div>Число: ${game.secret_number}</div>
                        <div>Результат: ${outcome}</div>
                    </div>
                    <button class="replay-btn" onclick="showReplay(${game.id})">Показать ходы</button>
                </div>
            `;
        }).join('');
    } catch (e) {
        container.innerHTML = '<p class="no-games">Ошибка загрузки данных</p>';
    }
}

async function showReplay(gameId) {
    try {
        const game = await apiGetGame(gameId);
        
        const modal = document.getElementById('replay-modal');
        const content = document.getElementById('replay-content');
        
        let html = `
            <h3>Партия #${game.id}</h3>
            <p><strong>Игрок:</strong> ${game.player_name}</p>
            <p><strong>Загаданное число:</strong> ${game.secret_number}</p>
            <p><strong>Результат:</strong> ${game.outcome || 'в процессе'}</p>
            <hr>
            <h4>Попытки:</h4>
        `;

        if (!game.attempts || game.attempts.length === 0) {
            html += '<p>Попыток не было</p>';
        } else {
            html += '<div class="replay-attempts">';
            game.attempts.forEach(a => {
                html += `<div class="replay-attempt">${a.attempt_number}. ${a.guess} → ${a.hints}</div>`;
            });
            html += '</div>';
        }

        content.innerHTML = html;
        modal.classList.remove('hidden');
    } catch (e) {
        alert('Ошибка загрузки игры');
    }
}

function closeModal() {
    document.getElementById('replay-modal').classList.add('hidden');
}

// ==================== Event Listeners ====================

document.addEventListener('DOMContentLoaded', () => {
    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(targetId).classList.add('active');
            
            if (targetId === 'history') displayGamesList();
        });
    });

    // Start game
    document.getElementById('start-btn').addEventListener('click', () => {
        startNewGame(document.getElementById('player-name').value);
    });
    
    document.getElementById('player-name').addEventListener('keypress', e => {
        if (e.key === 'Enter') startNewGame(document.getElementById('player-name').value);
    });

    // Make guess
    document.getElementById('guess-btn').addEventListener('click', () => {
        makeGuess(document.getElementById('guess-input').value);
    });
    
    document.getElementById('guess-input').addEventListener('keypress', e => {
        if (e.key === 'Enter') makeGuess(document.getElementById('guess-input').value);
    });
    
    document.getElementById('guess-input').addEventListener('input', e => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
    });

    // New game after victory
    document.getElementById('new-game-btn').addEventListener('click', resetGame);
});
