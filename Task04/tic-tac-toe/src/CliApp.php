<?php

namespace Eario13\TicTacToe;

use cli\Arguments;
use cli\Streams;
use Eario13\TicTacToe\Database\DatabaseManager;

// Добавляем use

class CliApp
{
    private Arguments $args;
    private DatabaseManager $dbManager; // Добавляем свойство для менеджера БД
    private string $dbFileName = 'tictactoe.sqlite'; // Имя файла БД

    public function __construct()
    {
        $this->args = new Arguments();
        $this->configureArguments();

        // Инициализируем DatabaseManager
        // Определяем путь к файлу БД относительно директории, где запускается bin/tic-tac-toe
        // Предполагаем, что файл БД будет в корне проекта
        $dbPath = __DIR__ . '/../../' . $this->dbFileName;
        $this->dbManager = new DatabaseManager($dbPath);
    }

    private function configureArguments(): void
    {
        $this->args->addFlag(['new-game', 'n'], 'Начать новую игру в крестики-нолики.');

        $this->args->addOption(
            ['board-size', 's'],
            [
                'default' => 3,
                'description' => 'Установить размер доски (например, --board-size=5 для доски 5x5). Мин. 3, макс. 10.'
            ]
        );

        $this->args->addOption(
            ['player-name', 'p'],
            [
                'default' => 'Игрок',
                'description' => 'Установить имя игрока (например, --player-name="Иван Петров").'
            ]
        );

        $this->args->addFlag(
            ['list-games', 'l'],
            'Показать список всех сохраненных игр.'
        );

        $this->args->addOption(
            ['replay-game', 'r'],
            [
                'default' => null,
                'description' => 'Повторить сохраненную игру по ID (например, --replay-game=1).'
            ]
        );
    }

    public function run(): void
    {
        $this->args->parse();

        if ($this->args['help']) {
            $this->showHelp();
            return;
        }

        if ($this->args['new-game']) {
            $this->startNewGame();
        } elseif ($this->args['list-games']) {
            $this->listGames();
        } elseif ($this->args['replay-game']) {
            $gameId = (int)$this->args['replay-game'];
            if ($gameId > 0) {
                $this->replayGame($gameId);
            } else {
                Streams::line('Неверный ID игры для повтора.');
            }
        } else {
            $this->showHelp();
        }
    }

    private function showHelp(): void
    {
        Streams::line('Использование: tic-tac-toe [опции]');
        Streams::line('');
        Streams::line('Опции:');
        Streams::line($this->args->getHelpScreen());
    }

    private function startNewGame(): void
    {
        $boardSize = (int) $this->args['board-size'];
        $humanPlayerName = (string) $this->args['player-name'];

        if ($boardSize < 3 || $boardSize > 10) {
            Streams::line('Неверный размер доски. Должен быть от 3 до 10.');
            return;
        }

        Streams::line('Имя игрока: ' . $humanPlayerName);
        Streams::line('Размер доски: ' . $boardSize . 'x' . $boardSize);
        Streams::line('Игра будет сохранена в базу данных.');

        $game = new Game($boardSize, $humanPlayerName, $this->dbManager); // Передаем dbManager в игру
        $game->run();
    }

    private function listGames(): void
    {
        Streams::line('Список сохраненных игр:');
        $games = $this->dbManager->getAllGames();

        if (empty($games)) {
            Streams::line('Нет сохраненных игр.');
            return;
        }

        // Определяем ширину столбцов для более читаемого вывода
        $colWidths = [
           'ID' => 4,
           'Размер' => 8,
           'Игрок X' => 18,       // Увеличено с 15 до 18
           'Игрок O' => 18,       // Увеличено с 15 до 18
           'Победитель' => 12,    // Можно оставить 12, или увеличить до 14 для "Компьютер"
           'Ничья' => 7,
           'Начало' => 20
        ];

        // Строка заголовков
        $header =
           str_pad('ID', $colWidths['ID']) . ' | ' .
           str_pad('Размер', $colWidths['Размер']) . ' | ' .
           str_pad('Игрок X', $colWidths['Игрок X']) . ' | ' .
           str_pad('Игрок O', $colWidths['Игрок O']) . ' | ' .
           str_pad('Победитель', $colWidths['Победитель']) . ' | ' .
           str_pad('Ничья', $colWidths['Ничья']) . ' | ' .
           str_pad('Начало', $colWidths['Начало']);
        Streams::line($header);

        // Разделительная линия
        Streams::line(str_repeat('-', strlen($header)));

        foreach ($games as $game) {
            $winner = $game['winner'] ?? '-';

            // Если победитель - Компьютер, нам нужно полное имя для вывода
            if ($winner === $game['player_x_name'] && $game['player_x_name'] === 'Компьютер') {
                $winnerDisplay = 'Компьютер (X)';
            } elseif ($winner === $game['player_o_name'] && $game['player_o_name'] === 'Компьютер') {
                $winnerDisplay = 'Компьютер (O)';
            } elseif ($winner === $game['player_x_name']) {
                $winnerDisplay = $game['player_x_name'] . ' (X)';
            } elseif ($winner === $game['player_o_name']) {
                $winnerDisplay = $game['player_o_name'] . ' (O)';
            } else {
                $winnerDisplay = $winner; // Для ничьей или других случаев
            }


            $draw = $game['draw'] ? 'Да' : 'Нет';

            $mbPad = function (
                string $input,
                int $length,
                string $pad_string = ' ',
                int $pad_type = STR_PAD_RIGHT
            ): string {
                $diff = $length - mb_strlen($input);
                if ($diff <= 0) {
                    return $input;
                }
                return $input . str_repeat($pad_string, $diff);
            };

            $line = $mbPad((string)$game['id'], $colWidths['ID']) . ' | ' .
                $mbPad($game['board_size'] . 'x' . $game['board_size'], $colWidths['Размер']) . ' | ' .
                $mbPad($game['player_x_name'], $colWidths['Игрок X']) . ' | ' .
                $mbPad($game['player_o_name'], $colWidths['Игрок O']) . ' | ' .
                $mbPad($winnerDisplay, $colWidths['Победитель']) . ' | ' .
                $mbPad($draw, $colWidths['Ничья']) . ' | ' .
                $mbPad($game['start_time'], $colWidths['Начало']);
            Streams::line($line);
        }
        Streams::line('');
    }

    private function replayGame(int $gameId): void
    {
        Streams::line('Повтор игры #' . $gameId);

        $gameDetails = $this->dbManager->getGameDetails($gameId);
        if (!$gameDetails) {
            Streams::line('Игра с ID ' . $gameId . ' не найдена.');
            return;
        }

        $moves = $this->dbManager->getGameMoves($gameId);
        if (empty($moves)) {
            Streams::line('Для игры #' . $gameId . ' нет сохраненных ходов.');
            return;
        }

        $boardSize = $gameDetails['board_size'];
        $board = new Board($boardSize);

        Streams::line('Начинаем повтор игры ' . $gameId . ' (' . $boardSize . 'x' . $boardSize . ')');
        Streams::line('Игрок X: ' . $gameDetails['player_x_name'] . ', Игрок O: ' . $gameDetails['player_o_name']);
        Streams::line(
            'Победитель: ' . ($gameDetails['winner'] ?? 'Неизвестно') .
            ', Ничья: ' . ($gameDetails['draw'] ? 'Да' : 'Нет')
        );
        Streams::line('');

        $moveDelay = 1; // Задержка в секундах между ходами
        $moveCounter = 0;

        foreach ($moves as $move) {
            $row = $move['row'];
            $col = $move['col'];
            $playerSymbol = $move['player_symbol'];
            $moveNumber = $move['move_number'];

            $board->setCell($row, $col, $playerSymbol);

            Streams::line("Ход {$moveNumber}: {$playerSymbol} ставит в ({$row}+1), ({$col}+1)");
            $this->displayBoardForReplay($board); // Используем отдельный метод для отображения
            sleep($moveDelay);
            $moveCounter++;
        }

        if ($gameDetails['winner']) {
            Streams::line('Победитель: Игрок ' . $gameDetails['winner'] . '!');
        } elseif ($gameDetails['draw']) {
            Streams::line('Ничья!');
        } else {
            Streams::line('Игра завершилась без явного победителя или ничьи.');
        }

        Streams::line('Повтор игры завершен.');
    }

    // Метод для отображения доски, адаптированный для повтора
    private function displayBoardForReplay(Board $board): void
    {
        Streams::line(str_repeat('=', $board->getSize() * 4 + 1));
        for ($i = 0; $i < $board->getSize(); $i++) {
            $rowString = '|';
            for ($j = 0; $j < $board->getSize(); $j++) {
                $rowString .= ' ' . $board->getCell($i, $j) . ' |';
            }
            Streams::line($rowString);
            Streams::line(str_repeat('=', $board->getSize() * 4 + 1));
        }
        Streams::line(''); // Добавляем пустую строку для лучшей читаемости
    }
}
