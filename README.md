# Laravel Backend App

## Requirements

- PHP 8.2
- [Composer](https://getcomposer.org/download/)
- Docker
- Postgres 15

## Technology Stack

- **API**: RESTful API with Laravel
- **Database**: PostgreSQL

## Getting Started

Clone the repository:

```bash
git clone git@github.com:boilerplate/backend-app.git [APP_NAME]
```

Access the project and install all dependencies:

```bash
cd [APP_NAME]

# Install PHP dependencies
composer install
```

Set up your environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

## Database Setup

Before running the application, you need to set up the database. Make sure your database credentials are configured in the `.env` file, then run:

```bash
composer run db:build
```

## Usage

### Standard Development Mode

Start the development server:

```bash
composer run dev
```

This command starts the application using the default Laravel development server along with all necessary services (logs, Vite, queue workers, Reverb WebSocket server, and scheduler).

### Async Development Mode (Laravel Octane + Swoole)

For improved performance in your local environment, you can run the application using Laravel Octane with Swoole:

```bash
composer run dev:async
```

**Prerequisites:**

Before using the async mode, you need to install the Swoole PHP extension:

```bash
sudo apt-get install php8.2-swoole
```

**What's the difference?**

The `dev:async` command uses Laravel Octane with Swoole instead of the standard PHP development server. This provides:

- Better performance and faster response times
- Persistent application state between requests
- Improved handling of concurrent requests
- Asynchronous task execution capabilities

**Note:** All other services (logs, Vite, queue workers, Reverb, and scheduler) run the same way in both modes.

### Docker (Recommended)

For a complete development environment with all dependencies (PostgreSQL, Redis, Evolution API) running in containers:

```bash
docker compose up
```

This will start:

- **Application container** on port 3333
- **PostgreSQL database** on port 5432

The application code is mounted as a volume, so any changes you make will be reflected immediately without rebuilding the container.

**Note:** On the first build, migrations and seeders are automatically executed, so the database will be ready to use.

**Stopping the environment:**

```bash
docker compose down
```

## Contributing

Please read our contributing guidelines before submitting pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
