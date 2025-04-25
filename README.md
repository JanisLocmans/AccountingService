## Technical Stack

- PHP 8.2
- Symfony 7.2
- PostgreSQL 15
- Docker
- PHP_CodeSniffer for code quality
- OpenAPI/Swagger for API documentation

## Setup Instructions

### Prerequisites

- Docker
- Docker Compose

### Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd <repository-directory>
```

2. Run the automated setup script (Linux/Mac):

```bash
chmod +x setup.sh
./setup.sh
```

Or on Windows:

```bash
./setup.sh  # If using Git Bash
# OR
bash setup.sh  # If bash is installed
```

The setup script will:
- Create the .env file from the template
- Build and start Docker containers
- Install Composer dependencies
- Run database migrations
- Load sample data
- Set up log directories
- Start the exchange rate scheduler worker

### Manual Installation

If you prefer to set up the project manually, follow these steps:

1. Copy the environment configuration:

```bash
cp .env.dist .env
```

2. Customize the `.env` file with your settings (database credentials, etc.)

3. Build and start the Docker containers:

```bash
docker-compose up -d --build
```

4. Install Composer dependencies:

```bash
docker-compose exec app composer install
```

5. Run database migrations:

```bash
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

6. Load fixtures (sample data):

```bash
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

7. Set up log directories:

```bash
docker-compose exec app mkdir -p var/log
docker-compose exec app chmod -R 777 var/log
docker-compose exec app touch var/log/exchange_rates.log
docker-compose exec app chmod 666 var/log/exchange_rates.log
```

8. Start the exchange rate scheduler worker:

```bash
docker-compose exec app php bin/console app:simple-scheduler-worker
```

The application will be available at http://localhost

### Permissions

The Docker setup automatically handles permissions for the log directories. If you're running the application outside of Docker, you may need to set permissions manually:

```bash
# Create necessary directories
mkdir -p var/cache var/log

# Set permissions
chmod -R 777 var/cache var/log

# Create exchange rates log file
touch var/log/exchange_rates.log
chmod 666 var/log/exchange_rates.log
```

### Default Configuration

The application comes with the following default configuration:

#### Database
- **Database**: db1
- **Username**: user1
- **Password**: password1
- **Host**: db (Docker service name)
- **Port**: 5432

#### API Endpoints
- `GET /api/clients/{id}/accounts` - List accounts for a client
- `GET /api/accounts/{id}/transactions?offset=0&limit=10` - Get transaction history with pagination
- `POST /api/transfers` - Transfer funds between accounts

## API Documentation

### List Client Accounts

```
GET /api/clients/{id}/accounts
```

Returns a list of accounts for a client.

### Get Transaction History

```
GET /api/accounts/{id}/transactions?offset=0&limit=10
```

Returns transaction history for an account with pagination.

Parameters:
- `offset`: Starting position (default: 0)
- `limit`: Number of transactions to return (default: 10, max: 100)

### Transfer Funds

```
POST /api/transfers
```

Transfer funds between two accounts.

Request body:
```json
{
  "sourceAccountId": 1,
  "destinationAccountId": 2,
  "amount": 100.50,
  "currency": "USD",
  "description": "Payment for services"
}
```

Notes:
- `amount` must be positive
- `currency` must be a valid 3-letter currency code
- `currency` must match either the source or destination account currency

## Running Tests

```bash
docker-compose exec app php bin/phpunit
```

## Design Decisions

### Currency Conversion

- Exchange rates are fetched from the [Exchange Rate API](https://open.er-api.com/v6/latest)
- Only USD, EUR, and GBP currencies are supported
- Rates are stored in the database for long-term persistence
- Rates are cached in memory for 1 hour to reduce database queries
- If the API is unavailable, the service falls back to database records
- Exchange rates are automatically updated every minute by a scheduled task
- Detailed logging of exchange rate updates including execution time and errors

#### API Configuration

The currency exchange API is configured in the `.env` file:

```
CURRENCY_EXCHANGE_API_URL=https://open.er-api.com/v6/latest
```

#### Supported Currencies

The application only supports the following currencies:
- USD
- EUR
- GBP 

#### Scheduled Tasks

The application includes a scheduler that automatically updates exchange rates every minute. To run the scheduler worker:

```bash
docker-compose exec app php bin/console app:simple-scheduler-worker
```

This will start a long-running process that checks every 10 seconds and executes the exchange rate update every minute. The worker logs detailed information about each exchange rate update, including execution time and any errors that occur.

The scheduler worker provides the following benefits:

1. **Automatic Updates**: Exchange rates are updated every minute
2. **Performance Tracking**: Each update is timed and logged
3. **Error Resilience**: Failures are logged but don't stop the scheduler
4. **Database Fallback**: If the API is unavailable, the system falls back to database records

#### Exchange Rate Logs

Detailed logs of exchange rate updates are stored in a dedicated log file:

```
var/log/exchange_rates.log
```

You can view the logs using:

```bash
docker-compose exec app cat var/log/exchange_rates.log
```

The logs include:
- Start time of each update
- Success/failure status
- Execution time in milliseconds
- Error messages (if any)
- Number of rates updated

### Code Quality

This project uses PHP_CodeSniffer to ensure code quality and adherence to coding standards. The code follows PSR-12 and additional strict standards.

#### Running Code Quality Checks

```bash
docker-compose exec app vendor/bin/phpcs
```

#### Automatically Fix Code Style Issues

```bash
docker-compose exec app vendor/bin/phpcbf
```

## API Documentation

The API is documented using OpenAPI/Swagger. You can access the interactive documentation at:

```
http://localhost/api/doc
```

You can also access the raw OpenAPI specification in JSON format at:

```
http://localhost/api/doc.json
```

This provides a user-friendly interface to explore the API endpoints, see the required parameters, and even try out the API directly from the browser.
