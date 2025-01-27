# Transaction Processing System

A secure and scalable transaction processing system built with Laravel, featuring concurrent operation handling, data integrity protection, and comprehensive testing.

## Features

-   RESTful API for transaction processing
-   Real-time balance tracking
-   Concurrent transaction handling
-   Data integrity protection
-   Comprehensive testing suite
-   API Authentication
-   Transaction history and filtering
-   Balance monitoring

## Requirements

-   PHP 8.1 or higher
-   Composer
-   SQLite (for testing)
-   Laravel 10.x

## Installation

1. Clone the repository:

```bash
git clone https://github.com/brian-chebon/transaction-processing-system.git
cd transaction-processing-system
```

2. Install dependencies:

```bash
composer install
```

3. Set up environment:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`:

```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

5. Run migrations:

```bash
touch database/database.sqlite
php artisan migrate
```

6. Seed the database:

```bash
php artisan db:seed
```

7. Install Sanctum:

```bash
php artisan sanctum:install
```

## Running Tests

Run the comprehensive test suite:

```bash
php artisan test
```

Run specific test categories:

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## API Documentation

### Authentication

All API endpoints require authentication using Laravel Sanctum. Include the bearer token in your request headers:

```
Authorization: Bearer <your-token>
```

### Endpoints

#### Transactions

-   **Create Transaction**

    ```
    POST /api/v1/transactions
    ```

    Body:

    ```json
    {
        "amount": 100.0,
        "type": "credit",
        "description": "Optional description"
    }
    ```

-   **Get Transaction History**
    ```
    GET /api/v1/transactions
    ```
    Query Parameters:
    -   type: credit/debit
    -   date_from: YYYY-MM-DD
    -   date_to: YYYY-MM-DD

#### Balance

-   **Get Current Balance**

    ```
    GET /api/v1/balance
    ```

-   **Get Detailed Balance**
    ```
    GET /api/v1/balance/details
    ```

### Error Handling

The API returns appropriate HTTP status codes and JSON responses:

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        "field": ["Error details"]
    }
}
```

## Project Structure

```
transaction-system/
├── app/
│   ├── Exceptions/
│   │   ├── AccountNotFoundException.php
│   │   ├── InsufficientFundsException.php
│   │   ├── InvalidTransactionException.php
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Providers/
│   ├── Repositories/
│   └── Services/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── tests/
    ├── Feature/
    └── Unit/
```

## Architecture

### Key Components

-   **Controllers**: Handle HTTP requests and responses
-   **Services**: Implement business logic
-   **Repositories**: Manage data access
-   **Models**: Define database structure and relationships
-   **Middleware**: Handle authentication and request processing

### Concurrency Handling

-   Database transactions for atomicity
-   Pessimistic locking for balance updates
-   Idempotency support via transaction references

### Security Features

-   API Authentication using Sanctum
-   Request validation
-   SQL injection protection
-   Rate limiting
-   Safe balance calculations

## Monitoring

### Logging Channels

Configure logging channels in `config/logging.php`:

```php
'channels' => [
    'transactions' => [
        'driver' => 'daily',
        'path' => storage_path('logs/transactions.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
    ],
    'metrics' => [
        'driver' => 'daily',
        'path' => storage_path('logs/metrics.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 7,
    ],
],
```

### View Logs

```bash
# View transaction logs
tail -f storage/logs/transactions.log

# View security logs
tail -f storage/logs/security.log

# View metrics logs
tail -f storage/logs/metrics.log
```

## Scaling Considerations

### Current Implementation

-   Database transaction isolation
-   Efficient indexing
-   Cache implementation
-   Request validation

### Future Improvements

1. **High Availability**

    - Load balancing
    - Database replication
    - Cache clustering

2. **Performance**

    - Queue implementation for async processing
    - Read replicas for balance queries
    - Horizontal scaling

3. **Monitoring**
    - Transaction logging
    - Performance metrics
    - Error tracking

## Troubleshooting

Common commands for troubleshooting:

```bash
# Clear cache
php artisan cache:clear

# Clear config
php artisan config:clear

# Rebuild autoload files
composer dump-autoload

# Reset database
php artisan migrate:fresh --seed
```

## Development Commands

Creating new components:

```bash
# Create controller
php artisan make:controller TransactionController

# Create model
php artisan make:model Transaction -mf

# Create test
php artisan make:test TransactionTest

# Create seeder
php artisan make:seeder TransactionSeeder
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request
