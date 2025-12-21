<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$dbPath = __DIR__ . '/../db/games.db';
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$db = new Database($dbPath);
$db->initTables();

// Главная страница - редирект на index.html
$app->get('/', function (Request $request, Response $response) {
    return $response
        ->withHeader('Location', '/index.html')
        ->withStatus(302);
});

// GET /games - список всех игр
$app->get('/games', function (Request $request, Response $response) use ($db) {
    try {
        $games = $db->getGames();
        $response->getBody()->write(json_encode($games, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// GET /games/{id} - ходы конкретной игры
$app->get('/games/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $gameId = (int) $args['id'];
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            $response->getBody()->write(json_encode(['error' => 'Game not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $moves = $db->getGameMoves($gameId);
        $response->getBody()->write(json_encode($moves, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


// POST /games - создание новой игры
$app->post('/games', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true) ?? [];
    
    if (!isset($data['board_size']) || !isset($data['player_x']) || !isset($data['player_o'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    try {
        $gameId = $db->createGame(
            (int) $data['board_size'],
            (string) $data['player_x'],
            (string) $data['player_o']
        );
        $response->getBody()->write(json_encode(['id' => $gameId]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// POST /step/{id} - сохранение хода
$app->post('/step/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $gameId = (int) $args['id'];
    $data = json_decode($request->getBody()->getContents(), true) ?? [];
    
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            $response->getBody()->write(json_encode(['error' => 'Game not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    
    if (!isset($data['symbol']) || !isset($data['row']) || !isset($data['col']) || !isset($data['move_number'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    try {
        $db->createMove(
            $gameId,
            (string) $data['symbol'],
            (int) $data['row'],
            (int) $data['col'],
            (int) $data['move_number']
        );
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// PUT /games/{id} - обновление результата игры
$app->put('/games/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $gameId = (int) $args['id'];
    $data = json_decode($request->getBody()->getContents(), true) ?? [];
    
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            $response->getBody()->write(json_encode(['error' => 'Game not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $winner = $data['winner'] ?? null;
        $isDraw = isset($data['is_draw']) ? (bool) $data['is_draw'] : false;
        
        $db->updateGame($gameId, $winner, $isDraw);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->run();
