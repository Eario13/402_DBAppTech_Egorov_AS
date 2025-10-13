<?php

namespace Eario13\TicTacToe;

use cli\Streams;
use cli\Arguments; // arguments больше не нужен в Game, но оставим для примера
use Eario13\TicTacToe\Database\DatabaseManager;

// Добавляем use

class Game
{
    private Board $board;
    private Player $playerX;
    private Player $playerO;
    private array $players;
    private string $currentPlayerSymbol;
    private string $humanSymbol;
    private DatabaseManager $dbManager; // Свойство для менеджера БД
    private int $gameId; // ID текущей игры в БД
    private int $moveNumber = 0; // Счетчик ходов

    public function __construct(int $boardSize, string $humanName, DatabaseManager $dbManager)
    {
        $this->board = new Board($boardSize);
        $this->dbManager = $dbManager; // Присваиваем менеджер БД

        $this->humanSymbol = (rand(0, 1) === 0) ? 'X' : 'O';

        Streams::line('Вы будете играть за ' . $this->humanSymbol);

        $computerSymbol = ($this->humanSymbol === 'X') ? 'O' : 'X';

        if ($this->humanSymbol === 'X') {
            $this->playerX = new HumanPlayer('X', $humanName);
            $this->playerO = new ComputerPlayer('O', 'Компьютер');
        } else {
            $this->playerX = new ComputerPlayer('X', 'Компьютер');
            $this->playerO = new HumanPlayer('O', $humanName);
        }

        // Сохраняем новую игру в БД
        $this->gameId = $this->dbManager->createGame(
            $boardSize,
            $this->playerX->getName(),
            $this->playerO->getName()
        );

        $this->players = ['X' => $this->playerX, 'O' => $this->playerO];
        $this->currentPlayerSymbol = 'X'; // Всегда начинаем с X
    }

    public function run(): void
    {
        Streams::line('Начинаем новую игру...');
        Streams::line('Игрок ' . $this->currentPlayerSymbol . ' ходит первым.');

        while (true) {
            $this->displayBoard();

            $currentPlayer = $this->players[$this->currentPlayerSymbol];

            Streams::line('Текущий игрок: ' . $this->currentPlayerSymbol .
                           ' (' . $currentPlayer->getName() . ')');

            [$row, $col] = $currentPlayer->makeMove($this->board);
            $this->board->setCell($row, $col, $this->currentPlayerSymbol);

            $this->moveNumber++;
            // Записываем ход в БД
            $this->dbManager->recordMove($this->gameId, $this->currentPlayerSymbol, $row, $col, $this->moveNumber);


            if ($this->board->checkWin($this->currentPlayerSymbol)) {
                $this->displayBoard();
                Streams::line('Игрок ' . $this->currentPlayerSymbol . ' победил!');
                $this->dbManager->endGame($this->gameId, $this->currentPlayerSymbol); // Обновляем БД: победитель
                break;
            }

            if ($this->board->isFull()) {
                $this->displayBoard();
                Streams::line('Ничья!');
                $this->dbManager->endGame($this->gameId, null, true); // Обновляем БД: ничья
                break;
            }

            $this->switchPlayer();
        }

        Streams::line('Игра окончена. Результаты сохранены в базу данных.');
    }

    private function displayBoard(): void
    {
        Streams::line(str_repeat('-', $this->board->getSize() * 4 + 1));
        for ($i = 0; $i < $this->board->getSize(); $i++) {
            $rowString = '|';
            for ($j = 0; $j < $this->board->getSize(); $j++) {
                $rowString .= ' ' . $this->board->getCell($i, $j) . ' |';
            }
            Streams::line($rowString);
            Streams::line(str_repeat('-', $this->board->getSize() * 4 + 1));
        }
    }

    private function switchPlayer(): void
    {
        $this->currentPlayerSymbol = ($this->currentPlayerSymbol === 'X') ? 'O' : 'X';
    }
}
