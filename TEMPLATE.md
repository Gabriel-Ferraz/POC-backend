# Laravel Backend Boilerplate

## Overview

This is a production-ready Laravel 12 boilerplate designed to kickstart your backend API projects. It comes pre-configured with modern tooling, best practices, and a solid foundation for building scalable RESTful APIs.

## What's Included

### Core Stack

- **Laravel 12** - Latest version with PHP 8.2+
- **PostgreSQL 15** - Robust relational database
- **Laravel Octane + Swoole** - High-performance async server
- **Docker Compose** - Complete containerized development environment

### Development Tools

- **Pest v3** - Modern PHP testing framework
- **Laravel Pint** - Opinionated code formatter
- **Laravel Boost** - Enhanced development experience with MCP
- **Husky + Lint-staged** - Git hooks for code quality
- **Concurrently** - Run multiple processes in development

### API Tools

- **Swagger UI** - Interactive API documentation
- **Laravel Pail** - Beautiful log tailing
- **Vite** - Fast frontend asset bundling

### Architecture

- **Clean Code Principles** - Early returns, immutability, small methods
- **Repository Pattern Ready** - Easy to implement
- **Service Layer** - Business logic separation
- **English Codebase** - All code, comments, and variables in English

## Quick Setup

### Using the Setup Script (Recommended)

```bash
./setup.sh
```

The script will guide you through:

1. Reinitializing git history (optional)
2. Setting project name and organization
3. Updating configuration files
4. Installing dependencies
5. Setting up the database
6. Creating initial commit

### Manual Setup

1. **Clone and clean**:
```bash
git clone <repo-url> my-project
cd my-project
rm -rf .git
git init
```

2. **Update `composer.json`**:
```json
{
  "name": "your-org/your-project",
  "description": "Your project description"
}
```

3. **Install and configure**:
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

4. **Update `.env`**:
- Set `APP_NAME`
- Set `DB_DATABASE`
- Configure other environment variables as needed

5. **Initialize database**:
```bash
composer run db:build
```

## Development Workflows

### Docker (Recommended for Teams)

```bash
# Start everything
docker compose up

# Stop everything
docker compose down
```

Benefits:
- Consistent environment across team
- No local PHP/PostgreSQL installation needed
- Isolated from other projects

### Local Development

#### Standard Mode

```bash
composer run dev
```
Best for: Simple projects, debugging

#### Async Mode (Octane)

```bash
# One-time: Install Swoole
sudo apt-get install php8.2-swoole

# Run with Octane
composer run dev:async
```

Best for: Performance testing, production-like environment

## Available Commands

### Development

```bash
composer run dev          # Standard dev server + logs
composer run dev:async    # Octane server + logs
```

### Database

```bash
composer run db:build     # Run migrations with seeders
composer run db:rebuild   # Fresh migration (WARNING: deletes data)
```

### Testing

```bash
composer run test         # All tests
composer run test:unit    # Unit tests only
composer run test:feature # Feature tests only
```

### Code Quality

```bash
composer run lint         # Format code with Pint
npm run build            # Build frontend assets
```

## Customization Guide

### 1. Remove Boilerplate Files

After setup, you may want to remove:

- `TEMPLATE.md` (this file)
- `setup.sh` (if no longer needed)

### 2. Configure Services

Update `.env` with your services:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 3. API Documentation

Update Swagger configuration in `config/swagger-ui.php`:

```php
'doc_file' => resource_path('docs/api.yaml'),
```

Add your API specs to `resources/docs/api.yaml`

### 4. Add Your Models and Controllers

```bash
php artisan make:model Post -mfsc
# Creates: Model, Migration, Factory, Seeder, Controller
```

### 5. Set Up Testing

Create your first test:

```bash
php artisan make:test PostTest
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/    # API endpoints
│   ├── Middleware/     # Request middleware
│   └── Requests/       # Form requests
├── Models/             # Eloquent models
├── Services/           # Business logic
├── Repositories/       # Data access layer (add as needed)
└── Support/            # Helpers and utilities

database/
├── migrations/         # Database schema
├── seeders/           # Test data
└── factories/         # Model factories

tests/
├── Feature/           # Integration tests
└── Unit/              # Unit tests

.claude/               # Claude AI context and memory
.taskmaster/           # Task Master configuration
```

## Code Style Guidelines

This project follows Laravel conventions plus:

- **English only** - Code, comments, variables
- **Early returns** - Reduce nesting
- **Immutability** - Use `readonly` properties
- **Small methods** - Max ~20 lines
- **Small classes** - Max ~200 lines
- **No dead code** - Remove commented code
- **One class per file**

See `CLAUDE.md` for full guidelines.

## Production Deployment

### Environment Setup

1. Set `APP_ENV=production`
2. Set `APP_DEBUG=false`
3. Configure real database credentials
4. Set up cache driver (Redis recommended)
5. Configure queue driver (Redis/SQS recommended)
6. Set up mail service
7. Enable HTTPS

### Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Using Octane in Production

```bash
php artisan octane:start --server=swoole --host=0.0.0.0 --port=3333 --workers=4
```

Consider using:

- Supervisor to keep Octane running
- Nginx as reverse proxy
- Redis for cache and sessions

## Troubleshooting

### Database Connection Issues

```bash
# Check PostgreSQL is running
docker compose ps

# Verify .env credentials
php artisan config:clear
```

### Swoole Installation Failed

```bash
# Ubuntu/Debian
sudo apt-get install php8.2-swoole

# macOS (requires Homebrew PHP)
pecl install swoole
```

### Tests Failing

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear

# Run with verbose output
./vendor/bin/pest -v
```

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Pest Documentation](https://pestphp.com)
- [Laravel Octane](https://laravel.com/docs/octane)
- [Laravel Boost](https://github.com/laravel/boost)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## Support

For issues with the boilerplate itself, please open an issue on the repository.

For Laravel-specific questions, refer to the [Laravel documentation](https://laravel.com/docs) or [Laracasts forum](https://laracasts.com/discuss).
