export default class Board {
    constructor(size) {
        this.size = size;
        this.grid = Array.from({ length: size }, () => Array(size).fill(null));
    }

    getSize() { return this.size; }

    getCell(row, col) { return this.grid[row][col]; }

    makeMove(row, col, symbol) {
        if (this.isValidMove(row, col)) {
            this.grid[row][col] = symbol;
            return true;
        }
        return false;
    }

    isValidMove(row, col) {
        return row >= 0 && row < this.size && col >= 0 && col < this.size && this.grid[row][col] === null;
    }

    isFull() {
        return this.grid.every(row => row.every(cell => cell !== null));
    }

    checkWin(symbol) {
        const s = this.size;
        // Строки и столбцы
        for (let i = 0; i < s; i++) {
            if (this.grid[i].every(c => c === symbol)) return true;
            if (this.grid.map(r => r[i]).every(c => c === symbol)) return true;
        }
        // Диагонали
        if (this.grid.every((r, i) => r[i] === symbol)) return true;
        if (this.grid.every((r, i) => r[s - 1 - i] === symbol)) return true;

        return false;
    }
}