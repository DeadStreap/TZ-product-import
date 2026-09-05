# Product Import

Приложение для импорта товаров из XLSX-файлов с асинхронной обработкой через очередь сообщений.

**Стек:** Slim 4 + Doctrine ORM (backend), Angular 18 + NgRx (frontend), MySQL 8.0, RabbitMQ

## Быстрый старт

```bash
cp .env.example .env
make setup          # сборка + создание схемы + сиды
make frontend-dev   # в отдельном терминале
```

Открыть `http://localhost:4200` — логин: `admin@example.com` / `password`

## Команды

| Команда | Описание |
|---------|----------|
| `make build` | Сборка Docker-образов |
| `make up` | Запуск всех сервисов |
| `make down` | Остановка всех сервисов |
| `make setup` | Сборка + создание схемы + сиды |
| `make migrate` | Запуск Doctrine миграций |
| `make seed` | Заполнение БД тестовыми данными (admin + 10 товаров) |
| `make schema-create` | Удаление и пересоздание всех таблиц |
| `make frontend-dev` | Запуск Angular dev-сервера |
| `make frontend-build` | Сборка фронтенда для продакшена |
| `make test` | Запуск всех тестов |
| `make test-unit` | Только unit-тесты |
| `make test-integration` | Только integration-тесты |
| `make lint` | Проверка PHPStan + CS Fixer |
| `make fix` | Автоисправление стиля кода |
| `make logs` | Логи приложения |

## Архитектура

```
├── backend/
│   ├── bin/console              # CLI-команды
│   ├── docker/Dockerfile        # Multi-stage сборка PHP 8.4
│   ├── migrations/              # Doctrine миграции
│   └── src/App/
│       ├── Config/              # Маршруты, middleware, DI
│       ├── Console/             # Команды: schema, seed, consumer
│       ├── Controllers/         # Auth, Import, Product, Health
│       ├── DTO/                 # ImportResult, ProductFilter
│       ├── Entities/            # Product, Attribute, Image, Task, User
│       ├── Enums/               # ImportStatus
│       ├── Messages/            # Асинхронный обработчик Messenger
│       ├── Middleware/           # Auth (JWT), RateLimit
│       ├── Repositories/        # Слой доступа к данным
│       └── Services/            # Import, ImageDownload, Auth
├── frontend/
│   └── src/app/
│       ├── core/                # Interceptor, guard, сервисы
│       ├── features/            # Auth, Import, Products (NgRx)
│       └── shared/              # Модели, SpinnerComponent
├── docs/openapi.yaml            # Документация API
├── docker-compose.yml           # app, db, nginx, rabbitmq, messenger
├── .env.example                 # Шаблон переменных окружения
└── Makefile                     # Все команды проекта
```

## API

Полная спецификация: [`docs/openapi.yaml`](docs/openapi.yaml)

| Метод | Эндпоинт | Auth | Описание |
|-------|----------|------|----------|
| GET | `/api/health` | Нет | Проверка работоспособности |
| POST | `/api/auth/login` | Нет | Получение JWT-токена |
| POST | `/api/import` | Да | Загрузка XLSX для импорта |
| GET | `/api/import/{id}/status` | Да | Получение статуса импорта |
| GET | `/api/products` | Да | Список товаров (пагинация, фильтры) |
| GET | `/api/products/{id}` | Да | Карточка товара |

## Сервисы Docker

| Сервис | Порт | Описание |
|--------|------|----------|
| app | 8080 | PHP-FPM бэкенд |
| db | 3306 | MySQL 8.0 |
| nginx | 80 | Reverse proxy |
| rabbitmq | 5672 | Брокер сообщений (AMQP) |
| messenger | - | Консьюмер очереди |

## Разработка

```bash
make lint    # PHPStan (level 5)
make fix     # Исправление стиля кода
make test    # Запуск тестов
```

## Технологии

- **Backend:** PHP 8.4, Slim 4, Doctrine ORM, Symfony Messenger, Firebase JWT
- **Frontend:** Angular 18, NgRx, Tailwind CSS 3.4, TypeScript (strict)
- **Инфраструктура:** Docker, MySQL 8.0, RabbitMQ, Nginx
- **Качество кода:** PHPStan level 5, PHP CS Fixer (PER-CS2.0)
