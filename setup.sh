#!/bin/bash

echo "🚀 Setting up Translation Management Service..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Copy environment file
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose up -d

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Install dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-interaction

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Create storage link
echo "🔗 Creating storage link..."
docker-compose exec -T app php artisan storage:link

# Cache configuration
echo "⚡ Caching configuration..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache

echo ""
echo "✅ Setup completed successfully!"
echo ""
echo "📊 To populate test data, run:"
echo "   docker-compose exec app php artisan translations:populate 100000"
echo ""
echo "🧪 To run tests:"
echo "   docker-compose exec app php artisan test"
echo ""
echo "🌐 API is available at: http://localhost:8000/api"
echo "📚 Documentation: See README.md and openapi.yaml"
echo ""
echo "Happy coding! 🎉"
