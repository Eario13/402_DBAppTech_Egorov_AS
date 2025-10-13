<?php

namespace Eario13\TicTacToe;

abstract class Player
{
    protected string $symbol;
    protected string $name; // Добавляем свойство name

    public function __construct(string $symbol, string $name) // Добавляем name в конструктор
    {
        $this->symbol = $symbol;
        $this->name = $name;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function makeMove(Board $board): array;
}
