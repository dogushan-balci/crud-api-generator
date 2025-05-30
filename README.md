# CRUD API Generator

Automatic CRUD API generator for MySQL/MariaDB databases.

## Features

- MySQL/MariaDB database support
- Automatic table discovery
- RESTful API endpoints
- CRUD operations
- Security and authentication
- Error handling
- API documentation
- Docker support

## Requirements

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Docker and Docker Compose (optional)

## Installation

### Installation via Composer

```bash
composer require crud-api-generator/crud-api-generator
```

### Installation via Docker

```bash
git clone https://github.com/dogushan.balci/crud-api-generator.git
cd crud-api-generator
cp .env.example .env
docker-compose up -d
```

## Usage

```php
use CRUDAPIGenerator\Core\APIGenerator;

$config = [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'root',
    'password' => 'secret'
];

$api = new APIGenerator($config);
$api->generate();
```

## API Endpoints

- `GET /api/{table}` - List all records
- `GET /api/{table}/{id}` - Get a single record
- `POST /api/{table}` - Create a new record
- `PUT /api/{table}/{id}` - Update a record
- `DELETE /api/{table}/{id}` - Delete a record

## Security

- API Key authentication
- CORS protection
- Rate limiting
- SQL injection protection
- XSS protection

## Testing

```bash
composer test
```

## Code Quality

```bash
composer check
```

## License

MIT

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Contact

https://dogushanbalci.com 