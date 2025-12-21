import Board from './board.js';
import { Player, ComputerPlayer } from './player.js';

export default class Game {
    constructor(config) {
        this.board = new Board(config.boardSize);
        this.onMove = config.onMove;
        this.onEnd = config.onEnd;
        this.onStatus = config.onStatus;

        const isHumanFirst = Math.random() < 0.5;
        const humanSymbol = isHumanFirst ? 'X' : 'O';
        const computerSymbol = isHumanFirst ? 'O' : 'X';

        this.human = new Player(humanSymbol, config.playerName);
        this.computer = new ComputerPlayer(computerSymbol);
        
        this.players = {
            [humanSymbol]: this.human,
            [computerSymbol]: this.computer
        };

        this.currentSymbol = 'X';
        this.isActive = true;
        this.moveCount = 0;
    }

    start() {
        this.onStatus(`Игра началась. Вы играете за ${this.human.symbol}. Ход: ${this.currentSymbol}`);
        this.checkTurn();
    }

    async checkTurn() {
        if (!this.isActive) return;

        const currentPlayer = this.players[this.currentSymbol];

        if (currentPlayer.isComputer) {
            await new Promise(r => setTimeout(r, 600));
            const move = currentPlayer.getMove(this.board);
            if (move) this.processMove(move.r, move.c);
        }
    }

    humanMove(row, col) {
        if (!this.isActive) return;
        const currentPlayer = this.players[this.currentSymbol];
        
        if (!currentPlayer.isComputer && this.board.isValidMove(row, col)) {
            this.processMove(row, col);
        }
    }

    processMove(row, col) {
        const symbol = this.currentSymbol;
        this.board.makeMove(row, col, symbol);
        this.moveCount++;

        if (this.onMove) this.onMove({ row, col, symbol, moveNum: this.moveCount });

        if (this.board.checkWin(symbol)) {
            this.finish(this.players[symbol].name, false);
        } else if (this.board.isFull()) {
            this.finish(null, true);
        } else {
            this.currentSymbol = this.currentSymbol === 'X' ? 'O' : 'X';
            this.onStatus(`Ход игрока: ${this.players[this.currentSymbol].name} (${this.currentSymbol})`);
            this.checkTurn();
        }
    }

    finish(winnerName, isDraw) {
        this.isActive = false;
        if (this.onEnd) this.onEnd({ winnerName, isDraw, winnerSymbol: isDraw ? null : this.currentSymbol });
    }
}
