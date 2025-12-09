<?php

require_once __DIR__ . '/../src/Database.php';

$dbPath = __DIR__ . '/../db/games.db';

function sendJson(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(string $message, int $status): void
{
    sendJson(['error' => $message], $status);
}

function getJsonBody(): array
{
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

function parseUri(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return ltrim($path, '/');
}

function getMethod(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

try {
    $db = new Database($dbPath);
    $db->initTables();
} catch (Exception $e) {
    sendError('Database error', 500);
}

$method = getMethod();
$path = parseUri();

if ($path === '' || $path === 'index.html') {
    $htmlFile = __DIR__ . '/index.html';
    if (file_exists($htmlFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($htmlFile);
        exit;
    }
}

$staticExtensions = ['html', 'css', 'js', 'png', 'jpg', 'gif', 'ico'];
$extension = pathinfo($path, PATHINFO_EXTENSION);
if (in_array($extension, $staticExtensions)) {
    return false;
}

if ($method === 'GET' && $path === 'games') {
    try {
        $games = $db->getGames();
        sendJson($games);
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
}

if ($method === 'GET' && preg_match('/^games\/(\d+)$/', $path, $matches)) {
    $gameId = (int) $matches[1];
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            sendError('Game not found', 404);
        }
        $moves = $db->getGameMoves($gameId);
        sendJson($moves);
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
}

if ($method === 'POST' && $path === 'games') {
    $data = getJsonBody();
    
    if (!isset($data['board_size']) || !isset($data['player_x']) || !isset($data['player_o'])) {
        sendError('Missing required fields', 400);
    }
    
    try {
        $gameId = $db->createGame(
            (int) $data['board_size'],
            (string) $data['player_x'],
            (string) $data['player_o']
        );
        sendJson(['id' => $gameId]);
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
}

if ($method === 'POST' && preg_match('/^step\/(\d+)$/', $path, $matches)) {
    $gameId = (int) $matches[1];
    $data = getJsonBody();
    
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            sendError('Game not found', 404);
        }
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
    
    if (!isset($data['symbol']) || !isset($data['row']) || !isset($data['col']) || !isset($data['move_number'])) {
        sendError('Missing required fields', 400);
    }
    
    try {
        $db->createMove(
            $gameId,
            (string) $data['symbol'],
            (int) $data['row'],
            (int) $data['col'],
            (int) $data['move_number']
        );
        sendJson(['success' => true]);
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
}

if ($method === 'PUT' && preg_match('/^games\/(\d+)$/', $path, $matches)) {
    $gameId = (int) $matches[1];
    $data = getJsonBody();
    
    try {
        $game = $db->getGame($gameId);
        if ($game === null) {
            sendError('Game not found', 404);
        }
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
    
    try {
        $winner = $data['winner'] ?? null;
        $isDraw = isset($data['is_draw']) ? (bool) $data['is_draw'] : false;
        
        $db->updateGame($gameId, $winner, $isDraw);
        sendJson(['success' => true]);
    } catch (Exception $e) {
        sendError('Database error', 500);
    }
}

sendError('Not found', 404);
