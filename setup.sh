#!/bin/bash

# Laravel Backend Boilerplate Setup Script
# This script helps you quickly set up a new project from this boilerplate

set -e

echo "🚀 Laravel Backend Boilerplate Setup"
echo "===================================="
echo ""

# Check if git is initialized
if [ -d ".git" ]; then
    read -p "⚠️  Git repository detected. Remove existing git history? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf .git
        git init
        echo "✓ Git reinitialized"
    fi
fi

# Get project details
read -p "Enter your project name (e.g., my-api): " PROJECT_NAME
read -p "Enter your organization name (e.g., my-org): " ORG_NAME
read -p "Enter project description: " PROJECT_DESC

# Update composer.json
echo ""
echo "📝 Updating composer.json..."
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s|\"name\": \"your-org/your-project\"|\"name\": \"${ORG_NAME}/${PROJECT_NAME}\"|g" composer.json
    sed -i '' "s|\"description\": \"Laravel Backend API Boilerplate\"|\"description\": \"${PROJECT_DESC}\"|g" composer.json
else
    # Linux
    sed -i "s|\"name\": \"your-org/your-project\"|\"name\": \"${ORG_NAME}/${PROJECT_NAME}\"|g" composer.json
    sed -i "s|\"description\": \"Laravel Backend API Boilerplate\"|\"description\": \"${PROJECT_DESC}\"|g" composer.json
fi
echo "✓ composer.json updated"

# Setup environment file
echo ""
echo "📝 Setting up .env file..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✓ .env file created"
else
    echo "⚠️  .env file already exists, skipping"
fi

# Update APP_NAME in .env
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s|APP_NAME=\"Laravel Backend\"|APP_NAME=\"${PROJECT_NAME}\"|g" .env
else
    sed -i "s|APP_NAME=\"Laravel Backend\"|APP_NAME=\"${PROJECT_NAME}\"|g" .env
fi

echo "✓ .env configured"

# Install dependencies
echo ""
read -p "Install dependencies now? (Y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    echo "📦 Installing PHP dependencies..."
    composer install

    echo "📦 Installing Node dependencies..."
    npm install

    echo "🔑 Generating application key..."
    php artisan key:generate

    echo "✓ Dependencies installed"
fi

# Database setup
echo ""
read -p "Run database migrations now? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🗄️  Running migrations..."
    composer run db:build
    echo "✓ Database setup complete"
fi

# Git initial commit
echo ""
if [ -d ".git" ]; then
    read -p "Create initial git commit? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        git add .
        git commit -m "chore: initial commit from boilerplate

Project: ${PROJECT_NAME}
Organization: ${ORG_NAME}

Co-Authored-By: Laravel Backend Boilerplate <noreply@boilerplate.dev>"
        echo "✓ Initial commit created"
    fi
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "  1. Update database credentials in .env if needed"
echo "  2. Start development: composer run dev"
echo "  3. Or use Docker: docker compose up"
echo ""
echo "Happy coding! 🎉"
