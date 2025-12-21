import Game from './game.js';
import * as api from './api.js';

const els = {
    newGameBtn: document.getElementById('new-game-btn'),
    listBtn: document.getElementById('list-games-btn'),
    boardSize: document.getElementById('board-size'),
    playerName: document.getElementById('player-name'),
    board: document.getElementById('game-board'),
    status: document.getElementById('status-text'),
    list: document.getElementById('games-list')
};

let currentGame = null;
let currentGameId = null;
let isReplaying = false;

function showError(message) {
    els.status.textContent = `Ошибка: ${message}`;
    console.error('App Error:', message);
}

async function checkServerAvailability() {
    try {
        await api.getGames();
        return true;
    } catch (e) {
        return false;
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const serverAvailable = await checkServerAvailability();
        if (serverAvailable) {
            els.status.textContent = "Готов к игре. Нажмите 'Новая игра'.";
        } else {
            showError("Сервер недоступен. Проверьте подключение.");
        }
    } catch (e) {
        console.error(e);
        showError("Ошибка подключения к серверу.");
    }
});

els.newGameBtn.addEventListener('click', startNewGame);
els.listBtn.addEventListener('click', toggleGamesList);

els.board.addEventListener('click', (e) => {
    if (isReplaying || !currentGame) return;
    
    const cell = e.target.closest('.cell');
    if (!cell) return;

    const row = parseInt(cell.dataset.r);
    const col = parseInt(cell.dataset.c);
    currentGame.humanMove(row, col);
});


async function startNewGame() {
    if (isReplaying) return;
    
    els.list.style.display = 'none';
    els.listBtn.textContent = 'Список партий (DB)';
    
    const size = parseInt(els.boardSize.value);
    const pName = els.playerName.value || 'Игрок';

    renderEmptyBoard(size);

    currentGame = new Game({
        boardSize: size,
        playerName: pName,
        onStatus: (msg) => els.status.textContent = msg,
        onMove: handleMove,
        onEnd: handleGameEnd
    });

    try {
        const result = await api.createGame(
            size, 
            currentGame.players['X'].name, 
            currentGame.players['O'].name
        );
        currentGameId = result.id;
        currentGame.start();
    } catch (e) {
        showError("Не удалось создать игру на сервере.");
        currentGame = null;
    }
}

async function handleMove(data) {
    const cell = document.querySelector(`.cell[data-r="${data.row}"][data-c="${data.col}"]`);
    if (cell) {
        cell.textContent = data.symbol;
        cell.classList.add(data.symbol.toLowerCase());
    }

    if (currentGameId && !isReplaying) {
        try {
            await api.saveMove(currentGameId, data.symbol, data.row, data.col, data.moveNum);
        } catch (e) {
            showError("Не удалось сохранить ход.");
        }
    }
}

async function handleGameEnd(result) {
    const msg = result.isDraw ? "Ничья!" : `Победитель: ${result.winnerName}!`;
    els.status.textContent = `Игра окончена. ${msg}`;
    
    if (currentGameId && !isReplaying) {
        try {
            await api.finalizeGame(currentGameId, result.winnerName, result.isDraw);
        } catch (e) {
            showError("Не удалось сохранить результат игры.");
        }
    }
    currentGame = null;
    currentGameId = null;
}

function renderEmptyBoard(size) {
    els.board.innerHTML = '';
    els.board.style.gridTemplateColumns = `repeat(${size}, 50px)`;
    
    for (let r = 0; r < size; r++) {
        for (let c = 0; c < size; c++) {
            const div = document.createElement('div');
            div.className = 'cell';
            div.dataset.r = r;
            div.dataset.c = c;
            els.board.appendChild(div);
        }
    }
}

async function toggleGamesList() {
    if (isReplaying) {
        isReplaying = false;
        return;
    }

    if (els.list.style.display === 'block') {
        els.list.style.display = 'none';
        els.listBtn.textContent = 'Список партий (DB)';
    } else {
        try {
            const games = await api.getGames();
            renderGamesList(games);
            els.list.style.display = 'block';
            els.listBtn.textContent = 'Скрыть список';
        } catch (e) {
            showError("Не удалось загрузить список игр.");
        }
    }
}

function renderGamesList(games) {
    els.list.innerHTML = '';
    if (games.length === 0) {
        els.list.innerHTML = '<div style="padding:10px;">Нет сохраненных игр.</div>';
        return;
    }

    games.forEach(g => {
        const div = document.createElement('div');
        div.className = 'game-record';
        const dateStr = new Date(g.start_time).toLocaleString();
        const res = g.winner ? `Победил ${g.winner}` : (g.is_draw ? 'Ничья' : 'Не закончена');
        div.textContent = `[ID:${g.id}] ${dateStr} - ${g.board_size}x${g.board_size} - ${res}`;
        
        div.onclick = () => startReplay(g);
        els.list.appendChild(div);
    });
}

async function startReplay(gameData) {
    isReplaying = true;
    currentGame = null;
    els.list.style.display = 'none';
    els.listBtn.textContent = 'Остановить просмотр';
    els.newGameBtn.disabled = true;
    
    els.status.textContent = `Воспроизведение игры #${gameData.id}...`;
    renderEmptyBoard(gameData.board_size);

    try {
        const moves = await api.getGameMoves(gameData.id);

        for (let move of moves) {
            if (!isReplaying) break;
            
            await new Promise(r => setTimeout(r, 800));
            
            if (!isReplaying) break;

            const cell = document.querySelector(`.cell[data-r="${move.row}"][data-c="${move.col}"]`);
            if (cell) {
                cell.textContent = move.symbol;
                cell.classList.add(move.symbol.toLowerCase());
            }
        }
        if(isReplaying) els.status.textContent = "Воспроизведение завершено.";
    } catch (e) {
        showError("Не удалось загрузить ходы игры.");
    } finally {
        isReplaying = false;
        els.newGameBtn.disabled = false;
        els.listBtn.textContent = 'Список партий (DB)';
    }
}
