DamnSmallRouter
===============

A really simple and small pathinfo php router

## Installation
Install via Composer:
```bash
composer require stefanobalocco/damn-small-router
```

Or add to `composer.json`:
```json
{
    "require": {
        "stefanobalocco/damn-small-router": "^1.0"
    }
}
```

Run `composer install` or `composer update`.

## Basic Usage

### Initialization
```php
use StefanoBalocco\DamnSmallRouter\Router;
$router = new Router();
```

### Adding Routes
```php
// Simple route with numeric placeholder
$router->AddRoute(
    '/user/#09#',                   // Route pattern
    function($userId, $isLogged) {  // Callback
        $user = getUserFromDatabase($userId);
        return renderUserProfile($user);
    }, 
    [ isLogged() ],                  // Additional variables (optional)
    'GET',                           // HTTP method (default: GET)
    $isAuthorized                    // Route availability (optional)
);

// Multiple methods for same route
$router->AddRoute('/profile', $profileGetHandler, [], 'GET');
$router->AddRoute('/profile', $profileUpdateHandler, [], 'POST');

// Conditional route availability
$router->AddRoute('/admin', $adminHandler, [], 'GET', userIsAdmin());
```

### Error Handling
Default error routes set HTTP response codes and return null. Customize them:
```php
$router->AddRoute403(function() { 
    http_response_code(403);
    return renderErrorPage("Access forbidden", 403);
});

$router->AddRoute404(function() { 
    http_response_code(404);
    return renderErrorPage("Page not found", 404);
});
```

### Router Execution
```php
echo $router->Route(); // Display the routed page
```

## Key Features
- Instance-based routing
- Pattern matching with placeholder support
- Automatic HTTP status code handling
- Support for multiple HTTP methods
- Customizable error handling
- Route weighting for prioritization
- Automatic HEAD request handling (converted to GET)

## Available Placeholders
- `#09#`: Numbers (`\d+`)
- `#AZ#`: Letters (`[a-zA-Z]+`)
- `#AZ09#`: Alphanumeric characters (`[\w]+`)

## Methods

### `AddRoute($route, $callback, $variables = [], $method = 'GET', $available = null)`
Adds a new route with:
- `$route`: URL pattern
- `$callback`: Function to execute
- `$variables`: Additional variables
- `$method`: HTTP method
- `$available`: Route condition

### `RouteAvailable($method = 'GET', $withoutConditions = false)`
Checks if a route is available for the current path.

### `Route()`
Executes routing for the current request and returns the callback result.
