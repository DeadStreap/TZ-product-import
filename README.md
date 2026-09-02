# Product Import Application

A full-stack application for importing products from XLSX files, built with Slim 4 (PHP) backend and Angular 18 frontend.

## Features

- Import products from XLSX files
- Server-side pagination and filtering
- JWT authentication
- Image downloading and storage
- Product attributes (key-value pairs)
- Discount calculation

## Tech Stack

### Backend
- PHP 8.4
- Slim 4
- Doctrine ORM
- MySQL 8.0
- RabbitMQ

### Frontend
- Angular 18
- NgRx (State Management)
- Tailwind CSS
- TypeScript (strict mode)

## Quick Start

### Prerequisites
- Docker & Docker Compose (Docker Desktop on macOS)
- Node.js 20 via nvm (Angular 18 is not compatible with Node 25)

### 1. Clone and setup

```bash
cp .env.example .env
make setup
```

This runs `build` → `up` → `migrate` (creates database tables).

### 2. Frontend development

```bash
source ~/.nvm/nvm.sh && nvm use 20
cd frontend
npm install
npm start
```

### 3. Access the application

- Frontend (dev): http://localhost:4200
- Frontend (nginx): http://localhost (after `npm run build`)
- Backend API: http://localhost/api/health
- RabbitMQ UI: http://localhost:15672 (guest/guest)

### 4. Login

- Email: admin@example.com
- Password: password

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | Authenticate user |
| GET | /api/health | Health check |
| POST | /api/import | Import XLSX file |
| GET | /api/import/{id}/status | Get import status |
| GET | /api/products | List products (paginated) |
| GET | /api/products/{id} | Get product details |

## Development

### Backend commands

```bash
make lint          # Run PHPStan and CS Fixer
make fix           # Auto-fix code style
make test          # Run PHPUnit tests
```

### Frontend commands

```bash
cd frontend
npm start          # Start dev server
npm run build      # Build for production
npm test           # Run unit tests
npm run lint       # Run linter
```

## Shutdown & Restart

```bash
# Stop all containers
sudo docker compose down

# Start all containers
sudo docker compose up -d
```

## Project Structure

```
product-import/
├── backend/                 # Slim 4 API
│   ├── src/App/            # Application code
│   ├── bin/                # Console commands
│   ├── docker/             # Docker configuration
│   └── tests/              # PHPUnit tests
├── frontend/               # Angular 18
│   ├── src/app/            # Application code
│   │   ├── core/           # Services, interceptors, guards
│   │   ├── features/       # Feature modules
│   │   └── shared/         # Models and shared components
│   └── docker/             # Docker configuration
├── docker-compose.yml      # Docker services
├── Makefile               # Development commands
└── .env.example           # Environment variables
```
