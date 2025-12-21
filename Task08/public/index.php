<?php

declare(strict_types=1);

// Front Controller для REST API

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Подключаем базу данных
require_once __DIR__ . '/../src/Database.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Убираем начальный слеш
$path = ltrim($path, '/');

// Роутинг
if ($path === '' || $path === 'index.html') {
    // Отдаём HTML страницу
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/game.html');
    exit;
}

// REST API роуты
if ($method === 'GET' && $path === 'games') {
    // GET /games - список всех игр
    $games = getAllGames();
    echo json_encode($games, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && preg_match('#^games/(\d+)$#', $path, $matches)) {
    // GET /games/{id} - данные об игре и её ходах
    $gameId = (int) $matches[1];
    $game = getGameById($gameId);
    
    if ($game === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Game not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $attempts = getAttemptsByGameId($gameId);
    $game['attempts'] = $attempts;
    
    echo json_encode($game, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && $path === 'games') {
    // POST /games - создать новую игру
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['player_name']) || !isset($input['secret_number'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $gameId = createGame($input['player_name'], $input['secret_number']);
    
    echo json_encode(['id' => $gameId], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && preg_match('#^step/(\d+)$#', $path, $matches)) {
    // POST /step/{id} - добавить ход в игру
    $gameId = (int) $matches[1];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['attempt_number']) || !isset($input['guess']) || !isset($input['hints'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Проверяем существование игры
    $game = getGameById($gameId);
    if ($game === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Game not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    saveAttempt($gameId, $input['attempt_number'], $input['guess'], $input['hints']);
    
    // Если игра выиграна, обновляем outcome
    if (isset($input['outcome'])) {
        updateGameOutcome($gameId, $input['outcome']);
    }
    
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

// 404 для неизвестных маршрутов
http_response_code(404);
echo json_encode(['error' => 'Not found'], JSON_UNESCAPED_UNICODE);
