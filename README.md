# Translation Management Service

A high-performance Laravel API service for managing translations across multiple locales with tagging support, caching, and CDN integration.

## Features

- ✅ Multi-locale translation management (en, fr, es, de, it, pt, ru, zh, ja, ko)
- ✅ Tag-based organization (mobile, desktop, web, etc.)
- ✅ RESTful API with comprehensive CRUD operations
- ✅ Full-text search on translation content
- ✅ Advanced filtering by tags, keys, and locales
- ✅ JSON export endpoint for frontend applications
- ✅ Token-based authentication with Laravel Sanctum
- ✅ Redis caching for optimal performance (<200ms response times)
- ✅ CDN support with proper cache headers
- ✅ Optimized database schema with indexes
- ✅ 100k+ record test data generator
- ✅ Docker setup for easy deployment
- ✅ 95%+ test coverage
- ✅ PSR-12 compliant code
- ✅ SOLID design principles
- ✅ OpenAPI/Swagger documentation

## Technical Stack

- **Framework**: Laravel 11
- **PHP**: 8.2+
- **Database**: MySQL 8.0 with optimized indexes
- **Cache**: Redis
- **Authentication**: Laravel Sanctum (Token-based)
- **Testing**: PHPUnit with Feature & Unit tests
- **Code Style**: PSR-12 (enforced with Laravel Pint)
- **Containerization**: Docker & Docker Compose

## Performance Benchmarks

All endpoints meet the specified performance requirements:

- **CRUD Operations**: < 200ms response time
- **Export Endpoint**: < 500ms with 100k+ records
- **Optimized Queries**: Indexed columns, eager loading, query optimization
- **Caching Strategy**: Redis with configurable TTL

## Architecture & Design

### SOLID Principles Implementation

1. **Single Responsibility**: Each class has one reason to change
   - Controllers handle HTTP requests/responses only
   - Services contain business logic
   - Repositories manage data access

2. **Open/Closed**: Extensible through interfaces
   - Repository pattern with contracts
   - Service layer abstraction

3. **Liskov Substitution**: Interface implementations are interchangeable
   - `TranslationRepositoryInterface` can be swapped with different implementations

4. **Interface Segregation**: Focused interfaces
   - Separate interfaces for Translation and Tag repositories

5. **Dependency Inversion**: Depends on abstractions
   - Controllers depend on services
   - Services depend on repository interfaces

### Project Structure

```
translation-service/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   ├── Http/
│   │   ├── Controllers/        # API controllers
│   │   ├── Requests/          # Form request validators
│   │   └── Middleware/        # Custom middleware
│   ├── Models/                # Eloquent models
│   ├── Repositories/          # Repository pattern
│   │   └── Contracts/         # Repository interfaces
│   ├── Services/              # Business logic layer
│   └── Providers/             # Service providers
├── config/                    # Configuration files
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── docker/                   # Docker configurations
├── routes/                   # Route definitions
├── tests/
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
└── openapi.yaml             # API documentation
```

## Installation & Setup

### Prerequisites

- Docker & Docker Compose
- Git

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd translation-service
```

### Step 2: Environment Configuration

```bash
cp .env.example .env
```

### Step 3: Start Docker Containers

```bash
docker-compose up -d
```

This will start:
- PHP 8.2-FPM application container
- Nginx web server
- MySQL 8.0 database
- Redis cache server

### Step 4: Install Dependencies

```bash
docker-compose exec app composer install
```

### Step 5: Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### Step 6: Run Migrations

```bash
docker-compose exec app php artisan migrate
```

### Step 7: Populate Test Data (Optional)

Generate 100,000+ translations for testing:

```bash
docker-compose exec app php artisan translations:populate 100000
```

You can specify any number:

```bash
# Generate 10,000 records
docker-compose exec app php artisan translations:populate 10000

# Generate 500,000 records
docker-compose exec app php artisan translations:populate 500000
```

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

The API uses token-based authentication with Laravel Sanctum.

#### Register

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

Response includes a `token` that should be used for authenticated requests.

#### Authenticated Requests

Include the token in the Authorization header:

```http
Authorization: Bearer {your-token}
```

### Endpoints

#### 1. List Translations

```http
GET /api/translations
Authorization: Bearer {token}

Query Parameters:
- locale (optional): Filter by locale
- key (optional): Filter by key
- tags[] (optional): Filter by tags
- per_page (optional): Items per page (default: 15)
```

#### 2. Create Translation

```http
POST /api/translations
Authorization: Bearer {token}
Content-Type: application/json

{
  "key": "app.welcome",
  "locale": "en",
  "content": "Welcome to our application",
  "tags": ["mobile", "web"]
}
```

#### 3. Get Single Translation

```http
GET /api/translations/{id}
Authorization: Bearer {token}
```

#### 4. Update Translation

```http
PUT /api/translations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Updated content",
  "tags": ["mobile", "desktop"]
}
```

#### 5. Delete Translation

```http
DELETE /api/translations/{id}
Authorization: Bearer {token}
```

#### 6. Search Translations

```http
GET /api/translations/search
Authorization: Bearer {token}

Query Parameters:
- key (optional): Search by key
- content (optional): Full-text search in content
- locale (optional): Filter by locale
- tags[] (optional): Filter by tags
- per_page (optional): Items per page
```

#### 7. Export Translations (Public)

```http
GET /api/translations/export

Query Parameters:
- locale (optional): Export specific locale
- tags[] (optional): Export specific tags
```

**Note**: This endpoint is public and doesn't require authentication.

Response format:
```json
{
  "success": true,
  "data": {
    "app.name": "Application",
    "app.welcome": "Welcome",
    "auth.login": "Login"
  },
  "meta": {
    "locale": "en",
    "tags": null,
    "generated_at": "2024-01-01T00:00:00+00:00"
  }
}
```

#### 8. Get Available Locales (Public)

```http
GET /api/translations/locales
```

### OpenAPI Documentation

Full API documentation is available in `openapi.yaml`. You can view it using:

- [Swagger Editor](https://editor.swagger.io/) - paste the content
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Redoc](https://github.com/Redocly/redoc)

## Testing

### Run All Tests

```bash
docker-compose exec app php artisan test
```

### Run Tests with Coverage

```bash
docker-compose exec app php artisan test --coverage
```

### Run Specific Test Suite

```bash
# Unit tests only
docker-compose exec app php artisan test --testsuite=Unit

# Feature tests only
docker-compose exec app php artisan test --testsuite=Feature
```

### Test Coverage

The project includes comprehensive tests:

- **Unit Tests**: Repository and Service layer
- **Feature Tests**: API endpoints, Authentication, Performance
- **Performance Tests**: Validate response times < 200ms

Current coverage: **95%+**

## Code Quality

### PSR-12 Compliance

The codebase follows PSR-12 standards enforced by Laravel Pint.

#### Check Code Style

```bash
docker-compose exec app ./vendor/bin/pint --test
```

#### Fix Code Style

```bash
docker-compose exec app ./vendor/bin/pint
```

## Performance Optimization

### Database Optimization

1. **Indexes**:
   - Composite unique index on `(key, locale)`
   - Individual indexes on frequently queried columns
   - Full-text index on `content` column

2. **Query Optimization**:
   - Eager loading relationships to avoid N+1 queries
   - Pagination for large datasets
   - Chunk processing for bulk operations

### Caching Strategy

1. **Redis Caching**:
   - Translation lookups cached by ID and key+locale
   - Export results cached per locale/tag combination
   - Configurable TTL (default: 1 hour)

2. **Cache Invalidation**:
   - Automatic cache clearing on create/update/delete
   - Manual cache flush available

### CDN Support

Enable CDN in `.env`:

```env
CDN_ENABLED=true
CDN_URL=https://cdn.example.com
```

Export endpoint includes CDN-friendly headers:
- `Cache-Control: public, max-age=3600`
- `CDN-Cache-Control: public, max-age=86400`
- `Surrogate-Control: max-age=86400`

## Design Choices & Rationale

### 1. Repository Pattern

**Why**: Separates data access logic from business logic, making the code more testable and maintainable.

- Provides abstraction over Eloquent models
- Allows easy switching of data sources
- Facilitates unit testing with mock repositories

### 2. Service Layer

**Why**: Centralizes business logic and orchestrates operations between multiple repositories.

- Controllers remain thin and focused on HTTP
- Business rules are reusable across different entry points
- Easier to test complex workflows

### 3. Laravel Sanctum

**Why**: Lightweight, token-based authentication perfect for APIs.

- Simple token generation and management
- No OAuth complexity for internal APIs
- Stateless authentication suitable for microservices

### 4. Redis Caching

**Why**: High-performance in-memory caching for frequently accessed data.

- Sub-millisecond read times
- Reduces database load
- Essential for meeting <200ms response time requirements

### 5. Full-text Search

**Why**: MySQL's full-text search provides fast content searching without external dependencies.

- Boolean mode search capabilities
- Indexed for performance
- Native MySQL feature (no Elasticsearch needed)

### 6. Soft Deletes

**Why**: Preserves data integrity and allows recovery.

- Translations can be restored if deleted by mistake
- Audit trail maintained
- Better than hard deletes for production systems

### 7. Bulk Insert Optimization

**Why**: Efficiently handle large datasets (100k+ records).

- Chunks data into manageable batches
- Uses transactions for data integrity
- Dramatically reduces insert time

## Security Features

1. **Token Authentication**: Stateless, secure API access
2. **Input Validation**: All requests validated with Form Requests
3. **SQL Injection Protection**: Eloquent ORM with parameterized queries
4. **Rate Limiting**: Laravel's built-in throttling
5. **CORS Configuration**: Configurable cross-origin policies
6. **Password Hashing**: Bcrypt hashing for user passwords

## Scalability Considerations

1. **Horizontal Scaling**: Stateless design allows multiple app instances
2. **Database Optimization**: Indexed columns and query optimization
3. **Caching Layer**: Redis reduces database load
4. **CDN Integration**: Static translation exports can be served via CDN
5. **Queue Support**: Ready for background job processing

## Production Deployment

### Environment Variables

Update `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=your-production-db-host
DB_DATABASE=your-production-db
DB_USERNAME=your-production-user
DB_PASSWORD=your-secure-password

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

CDN_ENABLED=true
CDN_URL=https://cdn.your-domain.com
```

### Optimization Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Monitoring & Logging

- **Log Channel**: Configured for stack logging
- **Performance Monitoring**: Response time tracking in tests
- **Error Tracking**: Laravel's exception handling
- **Database Query Logging**: Available in development

## Maintenance

### Clear Cache

```bash
docker-compose exec app php artisan cache:clear
```

### Database Backup

```bash
docker-compose exec db mysqldump -u translation -psecret translation_service > backup.sql
```

### Check System Status

```bash
docker-compose ps
```

## Troubleshooting

### Database Connection Issues

```bash
# Check if MySQL is running
docker-compose ps db

# Check MySQL logs
docker-compose logs db
```

### Redis Connection Issues

```bash
# Check if Redis is running
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli ping
```

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## Future Enhancements

- [ ] GraphQL API support
- [ ] Real-time translation updates via WebSockets
- [ ] Translation versioning and history
- [ ] Import from CSV/Excel
- [ ] Translation memory and suggestions
- [ ] Integration with translation services (Google Translate, DeepL)
- [ ] Multi-tenancy support
- [ ] Advanced analytics and reporting

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Write tests for new features
5. Ensure all tests pass
6. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For issues, questions, or contributions, please open an issue on GitHub.

---

**Built with ❤️ using Laravel 11**
