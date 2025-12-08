const API_BASE = '';

async function request(url, options = {}) {
    const response = await fetch(API_BASE + url, {
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        },
        ...options
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || `HTTP error ${response.status}`);
    }

    return data;
}

export async function getGames() {
    return request('/games');
}

export async function getGameMoves(gameId) {
    return request(`/games/${gameId}`);
}

export async function createGame(boardSize, playerX, playerO) {
    return request('/games', {
        method: 'POST',
        body: JSON.stringify({
            board_size: boardSize,
            player_x: playerX,
            player_o: playerO
        })
    });
}

export async function saveMove(gameId, symbol, row, col, moveNumber) {
    return request(`/step/${gameId}`, {
        method: 'POST',
        body: JSON.stringify({
            symbol,
            row,
            col,
            move_number: moveNumber
        })
    });
}

export async function finalizeGame(gameId, winner, isDraw) {
    return request(`/games/${gameId}`, {
        method: 'PUT',
        body: JSON.stringify({
            winner,
            is_draw: isDraw
        })
    });
}
