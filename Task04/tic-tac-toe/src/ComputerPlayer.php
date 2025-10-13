<?php

namespace Eario13\TicTacToe;

class ComputerPlayer extends Player
{
    // Конструктор наследуется, но можно явно вызвать parent
    public function __construct(string $symbol, string $name)
    {
        parent::__construct($symbol, $name);
    }

    public function makeMove(Board $board): array
    {
        $size = $board->getSize();
        $availableMoves = [];

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($board->isValidMove($row, $col)) {
                    $availableMoves[] = [$row, $col];
                }
            }
        }

        if (empty($availableMoves)) {
            throw new \RuntimeException('Нет доступных ходов для компьютера.');
        }

        // Здесь можно реализовать более "умный" AI, но для начала случайный ход подойдет
        return $availableMoves[array_rand($availableMoves)];
    }
}
