# Backend App

## Requirements

- PHP 8.2
- [Composer](https://getcomposer.org/download/)
- Node.js 20.x

## Technology Stack

- **API**: RESTful API with Laravel 12.x

## Getting Started

Clone the repository:

```bash
git clone git@gitlab.hugyourcustomer.ai:boilerplate/backend-app.git
```

Access the project and install all dependencies:

```bash
cd backend-app

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

Start the Docker containers:

```bash
docker composer up -d
```

Set up your environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Run database migrations and seeders:

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

## Build

Run the following command to create the Docker container application

```bash
docker build --no-cache -t backend_app:dev .
```

## Contributing

Please read our contributing guidelines before submitting pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
