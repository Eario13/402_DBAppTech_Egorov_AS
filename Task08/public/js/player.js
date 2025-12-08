export class Player {
    constructor(symbol, name, isComputer = false) {
        this.symbol = symbol;
        this.name = name;
        this.isComputer = isComputer;
    }
}

export class ComputerPlayer extends Player {
    constructor(symbol) {
        super(symbol, 'Компьютер', true);
    }

    getMove(board) {
        const emptyCells = [];
        const size = board.getSize();
        for (let r = 0; r < size; r++) {
            for (let c = 0; c < size; c++) {
                if (board.isValidMove(r, c)) {
                    emptyCells.push({ r, c });
                }
            }
        }
        if (emptyCells.length === 0) return null;
        return emptyCells[Math.floor(Math.random() * emptyCells.length)];
    }
}