DamnSmallRouter
===============

A really simple and small pathinfo php router

## Key Features
- Pattern matching with placeholder support
- Automatic HTTP status code handling
- Support for multiple HTTP methods
- Singleton pattern for router instance
- Customizable error handling
- Route weighting for prioritization

## Installation
You can install the library via Composer by running:
```bash
composer require stefanobalocco/damn-small-router
```

Or add it to your `composer.json`:
```json
{
    "require": {
        "stefanobalocco/damn-small-router": "^1.0"
    }
}
```

Then run `composer install` or `composer update`.

After installation, you can use the library via the `StefanoBalocco\DamnSmallRouter` namespace.

## Basic Usage

### Initialization
```php
use StefanoBalocco\DamnSmallRouter\Router;
$router = Router::GetInstance();
```

### Adding Routes
```php
Router::AddRoute(
    '/path/example/#09#', // Route pattern with placeholders
    function($parameter1) { /* route handling */ }, // Callback
    [], // Additional variables (optional)
    'GET', // HTTP method (default: GET)
    true // Route availability (optional)
);
```

### Available Placeholders
- `#09#`: Numbers (`\d+`)
- `#AZ#`: Letters (`[a-zA-Z]+`)
- `#AZ09#`: Alphanumeric characters (`[\w,]+`)

### Error Handling
```php
// Customize error responses
Router::AddRoute403(function() { /* 403 handling */ });
Router::AddRoute404(function() { /* 404 handling */ });
Router::AddRoute405(function() { /* 405 handling */ });
Router::AddRoute500(function() { /* 500 handling */ });
```

### Router Execution
```php
$response = Router::Route();
```

## Important Notes
- Routes are evaluated based on their "weight" calculated from pattern complexity
- The library automatically handles HEAD requests as GET
- If a route is not available (parameter `available` set to `false`), it generates a 403
- If the HTTP method doesn't match, it generates a 405
- If no route matches, it generates a 404
- If the callback is not executable, it generates a 500

## Available Methods

### `Router::GetInstance()`
Gets the singleton instance of the router.

### `Router::AddRoute($route, $callback, $variables = [], $method = 'GET', $available = null)`
Adds a new route.

### `Router::RouteAvailable($method = 'GET', $withoutConditions = false)`
Checks if a route is available for the current path.

### `Router::Route()`
Executes routing for the current request and returns the callback result.
