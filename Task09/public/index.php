<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Database;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Middleware для CORS
$app->add(function (Request $request, $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
});

// OPTIONS для CORS preflight
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// GET / - редирект на index.html
$app->get('/', function (Request $request, Response $response) {
    return $response
        ->withHeader('Location', '/index.html')
        ->withStatus(302);
});

// GET /index.html - отдаём HTML страницу
$app->get('/index.html', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/game.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

// GET /css/style.css
$app->get('/css/style.css', function (Request $request, Response $response) {
    $css = file_get_contents(__DIR__ . '/css/style.css');
    $response->getBody()->write($css);
    return $response->withHeader('Content-Type', 'text/css; charset=utf-8');
});

// GET /js/app.js
$app->get('/js/app.js', function (Request $request, Response $response) {
    $js = file_get_contents(__DIR__ . '/js/app.js');
    $response->getBody()->write($js);
    return $response->withHeader('Content-Type', 'application/javascript; charset=utf-8');
});

// GET /games - список всех игр
$app->get('/games', function (Request $request, Response $response) {
    $games = Database::getAllGames();
    $response->getBody()->write(json_encode($games, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// GET /games/{id} - данные об игре и её ходах
$app->get('/games/{id}', function (Request $request, Response $response, array $args) {
    $gameId = (int) $args['id'];
    $game = Database::getGameById($gameId);

    if ($game === null) {
        $response->getBody()->write(json_encode(['error' => 'Game not found'], JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(404);
    }

    $game['attempts'] = Database::getAttemptsByGameId($gameId);
    $response->getBody()->write(json_encode($game, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// POST /games - создать новую игру
$app->post('/games', function (Request $request, Response $response) {
    $input = json_decode($request->getBody()->getContents(), true);

    if (!isset($input['player_name']) || !isset($input['secret_number'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(400);
    }

    $gameId = Database::createGame($input['player_name'], $input['secret_number']);
    $response->getBody()->write(json_encode(['id' => $gameId], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

// POST /step/{id} - добавить ход в игру
$app->post('/step/{id}', function (Request $request, Response $response, array $args) {
    $gameId = (int) $args['id'];
    $input = json_decode($request->getBody()->getContents(), true);

    if (!isset($input['attempt_number']) || !isset($input['guess']) || !isset($input['hints'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(400);
    }

    $game = Database::getGameById($gameId);
    if ($game === null) {
        $response->getBody()->write(json_encode(['error' => 'Game not found'], JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(404);
    }

    Database::saveAttempt($gameId, $input['attempt_number'], $input['guess'], $input['hints']);

    if (isset($input['outcome'])) {
        Database::updateGameOutcome($gameId, $input['outcome']);
    }

    $response->getBody()->write(json_encode(['success' => true], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

$app->run();
