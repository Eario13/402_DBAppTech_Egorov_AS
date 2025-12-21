<?php

class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        $this->pdo = new PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function initTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                board_size INTEGER NOT NULL,
                player_x TEXT NOT NULL,
                player_o TEXT NOT NULL,
                start_time TEXT DEFAULT CURRENT_TIMESTAMP,
                end_time TEXT,
                winner TEXT,
                is_draw INTEGER DEFAULT 0
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                symbol TEXT NOT NULL,
                row INTEGER NOT NULL,
                col INTEGER NOT NULL,
                move_number INTEGER NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games(id)
            )
        ");
    }

    public function createGame(int $boardSize, string $playerX, string $playerO): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO games (board_size, player_x, player_o)
            VALUES (:board_size, :player_x, :player_o)
        ");
        $stmt->execute([
            ':board_size' => $boardSize,
            ':player_x' => $playerX,
            ':player_o' => $playerO
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getGames(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, board_size, player_x, player_o, start_time, end_time, winner, is_draw
            FROM games
            ORDER BY start_time DESC
        ");
        return $stmt->fetchAll();
    }

    public function getGame(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, board_size, player_x, player_o, start_time, end_time, winner, is_draw
            FROM games
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateGame(int $id, ?string $winner, bool $isDraw): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE games
            SET winner = :winner, is_draw = :is_draw, end_time = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':winner' => $winner,
            ':is_draw' => $isDraw ? 1 : 0
        ]);
        return $stmt->rowCount() > 0;
    }

    public function createMove(int $gameId, string $symbol, int $row, int $col, int $moveNumber): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO moves (game_id, symbol, row, col, move_number)
            VALUES (:game_id, :symbol, :row, :col, :move_number)
        ");
        $stmt->execute([
            ':game_id' => $gameId,
            ':symbol' => $symbol,
            ':row' => $row,
            ':col' => $col,
            ':move_number' => $moveNumber
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getGameMoves(int $gameId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, game_id, symbol, row, col, move_number
            FROM moves
            WHERE game_id = :game_id
            ORDER BY move_number ASC
        ");
        $stmt->execute([':game_id' => $gameId]);
        return $stmt->fetchAll();
    }
}
