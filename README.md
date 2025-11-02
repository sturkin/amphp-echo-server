# Amphp HTTP Echo Server

A simple HTTP echo server built with amphp for asynchronous networking in PHP. The server returns all request details (method, URI, headers, body) as a JSON response.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer install
```

## Running the Server

Start the echo server on default host (127.0.0.1) and port (8080):

```bash
php server.php
```

Or specify a custom host and port:

```bash
php server.php 0.0.0.0 9000
```

The server will display:
```
HTTP Echo server listening on http://127.0.0.1:8080
Press Ctrl+C to stop the server
```

## Testing the Server

### Using a Web Browser

Simply open your browser and navigate to:
```
http://127.0.0.1:8080
```

### Using curl

Test a GET request:
```bash
curl http://127.0.0.1:8080/api/users?id=123
```

Test a POST request with body:
```bash
curl -X POST http://127.0.0.1:8080/api/data \
  -H "Content-Type: application/json" \
  -d '{"name": "John", "age": 30}'
```

Test a PUT request:
```bash
curl -X PUT http://127.0.0.1:8080/api/update \
  -d "test data"
```

### Example Response

```json
{
    "method": "POST",
    "uri": "http://127.0.0.1:8080/api/data",
    "headers": {
        "host": ["127.0.0.1:8080"],
        "user-agent": ["curl/7.81.0"],
        "accept": ["*/*"],
        "content-type": ["application/json"],
        "content-length": ["27"]
    },
    "body": "{\"name\": \"John\", \"age\": 30}"
}
```

## Running Tests

```bash
./vendor/bin/phpunit
```

All tests include:
- Server configuration
- Connection acceptance
- Echo functionality for GET/POST/PUT requests
- Multiple sequential requests
- Concurrent requests handling
- Error handling

## Features

- HTTP protocol support (works in web browsers)
- Asynchronous request handling with amphp
- Supports multiple concurrent connections
- Echo functionality: returns all request details as JSON
- Graceful shutdown with signal handling (SIGINT, SIGTERM)
- Comprehensive test suite with 7 tests and 36 assertions

## Architecture

- `src/EchoServer.php` - Main HTTP server class with async request handling
- `server.php` - CLI entry point for running the server
- `tests/EchoServerTest.php` - PHPUnit tests for the HTTP echo server

The server uses amphp's event loop and HTTP server to handle multiple HTTP requests concurrently without blocking.

## Use Cases

This echo server is useful for:
- Testing HTTP clients
- Debugging HTTP requests
- Learning amphp HTTP server
- API development and testing
- Webhook testing