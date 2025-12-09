# Крестики-нолики (Tic-Tac-Toe) - SPA с SQLite

Веб-приложение "Крестики-нолики" реализованное как Single Page Application (SPA) с серверной базой данных SQLite и REST API на PHP.

## Описание проекта

Приложение позволяет:
- Играть в крестики-нолики на поле 3x3 или 5x5
- Выбирать режим игры: человек против человека или против компьютера
- Сохранять историю всех игр и ходов в базе данных SQLite
- Просматривать список завершённых игр
- Воспроизводить ходы любой сохранённой игры

### Архитектура

- **Frontend**: HTML, CSS, JavaScript (модульная структура)
- **Backend**: PHP с паттерном Front Controller
- **База данных**: SQLite
- **Обмен данными**: REST API с JSON

## Требования

- PHP 8.0 или выше
- Расширение PDO SQLite (обычно включено по умолчанию)

## Запуск приложения

1. Перейдите в директорию проекта:
   ```bash
   cd Task08
   ```

2. Запустите встроенный PHP-сервер:
   ```bash
   php -S localhost:3000 -t public
   ```

3. Откройте в браузере:
   - http://localhost:3000
   - или http://localhost:3000/index.html

## Структура проекта

```
Task08/
├── public/                 # Web root (публичные файлы)
│   ├── index.html         # Главная страница SPA
│   ├── index.php          # Front Controller (точка входа API)
│   ├── css/
│   │   └── style.css      # Стили
│   └── js/
│       ├── app.js         # Главный контроллер приложения
│       ├── api.js         # HTTP клиент для REST API
│       ├── board.js       # Модель игрового поля
│       ├── game.js        # Бизнес-логика игры
│       └── player.js      # Классы игроков
├── src/                    # Серверный код
│   └── Database.php       # Класс работы с SQLite
├── db/                     # База данных (создаётся автоматически)
│   └── games.db           # SQLite файл
├── tests/                  # Тесты
│   ├── ApiPropertyTest.php
│   └── DatabasePropertyTest.php
├── composer.json          # Зависимости PHP
├── phpunit.xml            # Конфигурация PHPUnit
└── README.md              # Документация
```

## REST API

### Базовый URL

```
http://localhost:3000
```

### Endpoints

#### GET /games

Получить список всех игр.

**Ответ:**
```json
[
  {
    "id": 1,
    "board_size": 3,
    "player_x": "Игрок",
    "player_o": "Компьютер",
    "start_time": "2025-12-08 10:30:00",
    "end_time": "2025-12-08 10:35:00",
    "winner": "Игрок",
    "is_draw": 0
  }
]
```

Игры отсортированы по `start_time` в порядке убывания (новые первыми).

---

#### GET /games/{id}

Получить ходы конкретной игры.

**Параметры:**
- `id` - идентификатор игры

**Ответ (200):**
```json
[
  {
    "id": 1,
    "game_id": 1,
    "symbol": "X",
    "row": 0,
    "col": 1,
    "move_number": 1
  }
]
```

Ходы отсортированы по `move_number` в порядке возрастания.

**Ошибка (404):**
```json
{
  "error": "Game not found"
}
```

---

#### POST /games

Создать новую игру.

**Тело запроса:**
```json
{
  "board_size": 3,
  "player_x": "Игрок",
  "player_o": "Компьютер"
}
```

**Ответ (200):**
```json
{
  "id": 1
}
```

**Ошибка (400):**
```json
{
  "error": "Missing required fields"
}
```

---

#### POST /step/{id}

Сохранить ход игры.

**Параметры:**
- `id` - идентификатор игры

**Тело запроса:**
```json
{
  "symbol": "X",
  "row": 0,
  "col": 1,
  "move_number": 1
}
```

**Ответ (200):**
```json
{
  "success": true
}
```

**Ошибка (404):**
```json
{
  "error": "Game not found"
}
```

---

#### PUT /games/{id}

Завершить игру (установить результат).

**Параметры:**
- `id` - идентификатор игры

**Тело запроса:**
```json
{
  "winner": "Игрок",
  "is_draw": false
}
```

Для ничьей:
```json
{
  "winner": null,
  "is_draw": true
}
```

**Ответ (200):**
```json
{
  "success": true
}
```

**Ошибка (404):**
```json
{
  "error": "Game not found"
}
```

## Запуск тестов

1. Установите зависимости:
   ```bash
   composer install
   ```

2. Запустите тесты:
   ```bash
   vendor/bin/phpunit
   ```

## Схема базы данных

### Таблица games

| Поле | Тип | Описание |
|------|-----|----------|
| id | INTEGER | Первичный ключ |
| board_size | INTEGER | Размер поля (3 или 5) |
| player_x | TEXT | Имя игрока X |
| player_o | TEXT | Имя игрока O |
| start_time | TEXT | Время начала игры |
| end_time | TEXT | Время окончания игры |
| winner | TEXT | Имя победителя (NULL если ничья) |
| is_draw | INTEGER | Флаг ничьей (0 или 1) |

### Таблица moves

| Поле | Тип | Описание |
|------|-----|----------|
| id | INTEGER | Первичный ключ |
| game_id | INTEGER | Внешний ключ на games.id |
| symbol | TEXT | Символ хода (X или O) |
| row | INTEGER | Номер строки |
| col | INTEGER | Номер столбца |
| move_number | INTEGER | Порядковый номер хода |
