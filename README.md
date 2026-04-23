# Laravel Backend Boilerplate

A production-ready Laravel 12 boilerplate for building RESTful APIs with modern tooling and best practices.

## Features

- **Laravel 12** with PHP 8.2
- **PostgreSQL** database
- **Laravel Octane** with Swoole for async performance
- **Pest v3** for testing
- **Laravel Pint** for code formatting
- **Laravel Boost** for enhanced DX
- **Docker Compose** for local development
- **Swagger UI** for API documentation
- **Husky** for git hooks
- **Concurrently** for running multiple processes

## Requirements

- PHP 8.2+
- [Composer](https://getcomposer.org/download/)
- Docker & Docker Compose
- PostgreSQL 15 (if not using Docker)
- Node.js 18+ (for frontend assets)

## Quick Start

### 1. Clone or Use as Template

```bash
# Clone the repository
git clone <repo-url> my-project-name
cd my-project-name

# Remove the original git history and start fresh
rm -rf .git
git init
```

### 2. Customize Your Project

Edit `composer.json` and update:

```json
{
  "name": "your-org/your-project",
  "description": "Your project description"
}
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 4. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Update .env with your project details
# At minimum, set: APP_NAME, DB_DATABASE
```

### 5. Database Setup

Make sure your database credentials are configured in `.env`, then:

```bash
# Run migrations
composer run db:build
```

## Development

### Option 1: Docker (Recommended)

The easiest way to get started. All dependencies are containerized:

```bash
# Start all services
docker compose up

# Stop services
docker compose down
```

This starts:

- Application container on port 3333
- PostgreSQL database on port 5432

Changes to your code are reflected immediately (hot reload).

### Option 2: Local Development

#### Standard Mode

```bash
composer run dev
```

Starts the Laravel development server with logs monitoring.

#### Async Mode (Laravel Octane + Swoole)

For better performance:

```bash
# Install Swoole first
sudo apt-get install php8.2-swoole

# Run with Octane
composer run dev:async
```

Benefits:

- Faster response times
- Persistent application state
- Better concurrent request handling
- Async task execution

## Available Commands

```bash
# Development
composer run dev          # Start standard dev server
composer run dev:async    # Start Octane dev server

# Database
composer run db:build     # Run migrations with seeders
composer run db:rebuild   # Fresh migrations with seeders

# Testing
composer run test         # Run all tests
composer run test:unit    # Run unit tests only
composer run test:feature # Run feature tests only

# Code Quality
composer run lint         # Format code with Pint
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/    # API Controllers
│   └── Middleware/     # Custom middleware
├── Models/             # Eloquent models
├── Services/           # Business logic
└── Support/            # Helpers and utilities

database/
├── migrations/         # Database migrations
└── seeders/           # Database seeders

tests/
├── Feature/           # Feature tests
└── Unit/              # Unit tests

.claude/               # Claude Code context
.taskmaster/           # Task Master configuration
```

## Next Steps

After setting up the boilerplate:

1. **Remove Example Code**: Clean up any example controllers/models
2. **Configure Services**: Set up mail, cache, queue drivers in `.env`
3. **API Documentation**: Update Swagger annotations
4. **Testing**: Write tests for your endpoints
5. **Deployment**: Configure your production environment

## Built With

- [Laravel 12](https://laravel.com) - PHP Framework
- [PostgreSQL](https://www.postgresql.org) - Database
- [Pest](https://pestphp.com) - Testing Framework
- [Laravel Octane](https://laravel.com/docs/octane) - High-performance server
- [Laravel Boost](https://github.com/laravel/boost) - Enhanced development experience
