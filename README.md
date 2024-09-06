# Laravel Auto Routing

Laravel Auto Routing is a package that simplifies route creation in your Laravel applications using PHP 8 attributes and intuitive naming conventions, while remaining compatible with Laravel's existing routing features.

## Features

- Automatic routing based on controller methods
- Use of PHP 8 attributes to define HTTP methods
- Automatic handling of route parameters
- Support for routes with specific HTTP methods or "any"
- Automatic route naming based on controller and method names
- Compatibility with Laravel's existing routing features (middlewares, prefixes, etc.)
- Seamless integration with Laravel's route groups

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher

## Installation

1. Install the package via Composer:

    ```bash
    composer require florddev/laravel-auto-routing
    ```

2. Add the service provider to your `config/app.php` file:

    ```php
    'providers' => [
        // Other service providers...
        Florddev\LaravelAutoRouting\AutoRoutingServiceProvider::class,
    ],
    ```

## Basic Usage

In your route file (e.g., `routes/web.php`), use the `auto` method to automatically register routes for a controller:

```php
Route::auto('/users', \App\Http\Controllers\UserController::class);
```

In your controller, use attributes to define HTTP methods:

```php
use Florddev\LaravelAutoRouting\Attributes\HttpGet;
use Florddev\LaravelAutoRouting\Attributes\HttpPost;
use Florddev\LaravelAutoRouting\Attributes\HttpPut;
use Florddev\LaravelAutoRouting\Attributes\HttpDelete;

class UserController extends Controller
{
    #[HttpGet]
    public function index() { /* ... */ }

    #[HttpGet]
    public function show(int $id) { /* ... */ }

    #[HttpPost]
    public function store() { /* ... */ }

    #[HttpPut]
    public function update(int $id) { /* ... */ }

    #[HttpDelete]
    public function destroy(int $id) { /* ... */ }
}
```

### Generated Routes

Here's an example of routes generated for a basic controller:

| Action  | Generated Route       | HTTP Method | Route Name      |
| ------- | --------------------- | ----------- | --------------- |
| index   | `/users`              | GET         | `users.index`   |
| show    | `/users/show/{id}`    | GET         | `users.show`    |
| store   | `/users/store`        | POST        | `users.store`   |
| update  | `/users/update/{id}`  | PUT         | `users.update`  |
| destroy | `/users/destroy/{id}` | DELETE      | `users.destroy` |

Note: The `index` method is automatically mapped to the root of the controller's prefix.

## Advanced Usage

### Adding Options

You can add additional options such as middlewares or prefixes:

```php
Route::auto('/admin/users', \App\Http\Controllers\Admin\UserController::class, [
    'middleware' => ['auth', 'admin'],
    'name' => 'admin.users.',
    'namespace' => 'Admin',
]);
```

### Custom URL and Route Names

You can customize URLs and route names using attribute parameters:

```php
use Florddev\LaravelAutoRouting\Attributes\HttpGet;
use Florddev\LaravelAutoRouting\Attributes\HttpPost;

class BlogController extends Controller
{
    #[HttpGet(url: "articles", name: "list")]
    public function index() { /* ... */ }

    #[HttpGet(url: "article/{slug}", name: "view")]
    public function show(string $slug) { /* ... */ }

    #[HttpPost(url: "new-article", middleware: "auth")]
    public function create() { /* ... */ }
}
```

### Optional Parameters

The package automatically handles optional parameters:

```php
use Florddev\LaravelAutoRouting\Attributes\HttpGet;

class ApiController extends Controller
{
    #[HttpGet]
    public function search(string $query, int $page = 1, string $sort = 'desc') { /* ... */ }
}
```

This will generate a route: `GET /api/search/{query}/{page?}/{sort?}`

### Using with Laravel Route Groups

Laravel Auto Routing works seamlessly with Laravel's route groups:

```php
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::auto('/users', UserController::class);
    Route::auto('/products', ProductController::class, ['middleware' => 'admin']);
});
```

### Subdomain Routing

You can use Auto Routing with subdomains:

```php
Route::auto('/support', \App\Http\Controllers\SupportController::class, [
    'domain' => 'help.example.com',
]);
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).