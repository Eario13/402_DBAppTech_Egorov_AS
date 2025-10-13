<?php

namespace Eario13\TicTacToe;

class HumanPlayer extends Player
{
    // Конструктор наследуется, но можно явно вызвать parent
    public function __construct(string $symbol, string $name)
    {
        parent::__construct($symbol, $name);
    }

    public function makeMove(Board $board): array
    {
        while (true) {
            echo $this->name . ' (' . $this->symbol . '), введите ваш ход (строка,столбец), например, 1,1: ';
            $input = trim(fgets(STDIN));
            $coordinates = array_map('trim', explode(',', $input));

            if (count($coordinates) === 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
                $row = (int) $coordinates[0] - 1;
                $col = (int) $coordinates[1] - 1;

                if ($board->isValidMove($row, $col)) {
                    return [$row, $col];
                }
                echo 'Неверный ход. Ячейка уже занята или находится за пределами поля. Попробуйте еще раз.' . PHP_EOL;
            } else {
                echo 'Неверный формат ввода. Пожалуйста, используйте формат строка,столбец (например, 1,1).' . PHP_EOL;
            }
        }
    }
}
