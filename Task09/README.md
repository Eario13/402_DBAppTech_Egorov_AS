# Tic-Tac-Toe SPA (Slim Framework)

Веб-приложение "Крестики-нолики" с REST API на микрофреймворке Slim.

## Требования

- PHP 8.0+
- Composer

## Установка

```bash
cd Task09
composer install
```

## Запуск

```bash
php -S localhost:3000 -t public
```

Откройте в браузере: http://localhost:3000

## API

| Метод | Путь | Описание |
|-------|------|----------|
| GET | /games | Получить список всех игр |
| GET | /games/{id} | Получить ходы игры |
| POST | /games | Создать новую игру |
| POST | /step/{id} | Добавить ход в игру |
| PUT | /games/{id} | Обновить статус игры |
