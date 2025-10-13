<?php

namespace Eario13\TicTacToe\Database;

use PDO;
use PDOException;

class DatabaseManager
{
    private PDO $pdo;
    private string $dbPath;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->createTables();
    }

    private function connect(): void
    {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // В реальном приложении здесь будет логирование
            throw new \RuntimeException("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    private function createTables(): void
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                board_size INTEGER NOT NULL,
                player_x_name TEXT NOT NULL,
                player_o_name TEXT NOT NULL,
                start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                end_time DATETIME NULL,
                winner TEXT NULL,
                draw BOOLEAN DEFAULT 0
            )",
            "CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                player_symbol TEXT NOT NULL,
                row INTEGER NOT NULL,
                col INTEGER NOT NULL,
                move_number INTEGER NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
            )"
        ];

        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
    }

    public function createGame(int $boardSize, string $playerXName, string $playerOName): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO games (board_size, player_x_name, player_o_name) VALUES " .
            "(:board_size, :player_x_name, :player_o_name)"
        );
        $stmt->execute([
            ':board_size' => $boardSize,
            ':player_x_name' => $playerXName,
            ':player_o_name' => $playerOName
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function recordMove(int $gameId, string $playerSymbol, int $row, int $col, int $moveNumber): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO moves (game_id, player_symbol, row, col, move_number) VALUES " .
            "(:game_id, :player_symbol, :row, :col, :move_number)"
        );
        $stmt->execute([
            ':game_id' => $gameId,
            ':player_symbol' => $playerSymbol,
            ':row' => $row,
            ':col' => $col,
            ':move_number' => $moveNumber
        ]);
    }

    public function endGame(int $gameId, ?string $winnerSymbol = null, bool $isDraw = false): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE games SET end_time = CURRENT_TIMESTAMP, winner = :winner, draw = :draw WHERE id = :id"
        );
        $stmt->execute([
            ':winner' => $winnerSymbol,
            ':draw' => (int)$isDraw,
            ':id' => $gameId
        ]);
    }

    public function getAllGames(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, board_size, player_x_name, player_o_name, winner, draw, start_time ' .
            'FROM games ORDER BY start_time DESC'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getGameDetails(int $gameId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, board_size, player_x_name, player_o_name, winner, draw, start_time ' .
            'FROM games WHERE id = ?'
        );
        $stmt->execute([$gameId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getGameMoves(int $gameId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM moves WHERE game_id = :game_id ORDER BY move_number ASC");
        $stmt->execute([':game_id' => $gameId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
