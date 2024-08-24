# Laravel Auto Routing

Laravel Auto Routing est un package qui simplifie la création de routes dans vos applications Laravel en utilisant des attributs PHP 8 et une convention de nommage intuitive, tout en restant compatible avec les fonctionnalités de routage existantes de Laravel.

## Caractéristiques

- Routage automatique basé sur les méthodes du contrôleur
- Utilisation d'attributs PHP 8 pour définir les méthodes HTTP
- Gestion automatique des paramètres de route
- Support des routes avec des méthodes HTTP spécifiques ou "any"
- Nommage automatique des routes basé sur le nom du contrôleur et de la méthode
- Compatibilité avec les middlewares, préfixes, et autres options de route Laravel

## Prérequis

- PHP 8.0 ou supérieur
- Laravel 8.0 ou supérieur

## Installation

Vous pouvez installer ce package via Composer :

```bash
composer require florddev/laravel-auto-routing
```

## Utilisation

Dans votre fichier de routes, utilisez la méthode `auto` pour enregistrer automatiquement les routes d'un contrôleur :

```php
Route::auto('/users', \App\Http\Controllers\UserController::class);
```

Vous pouvez également ajouter des options supplémentaires, telles que des middlewares ou des préfixes :

```php
Route::auto('/admin/users', \App\Http\Controllers\Admin\UserController::class, [
    'middleware' => ['auth', 'admin'],
    'name' => 'admin.users.',
    'namespace' => 'Admin',
]);
```

Dans votre contrôleur, utilisez les attributs pour définir les méthodes HTTP :

```php
use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost, HttpPut, HttpDelete};

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

## Exemples détaillés d'utilisation

Cette section présente divers scénarios d'utilisation du package Laravel Auto Routing, montrant le code de configuration, les routes générées, leurs noms, et d'autres détails pertinents.

### Exemple 1 : Configuration de base

Code :
```php
Route::auto('/users', \App\Http\Controllers\UserController::class);
```

Controller :
```php
use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost, HttpPut, HttpDelete};

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

Résultats:
| Actions | Route générées | Méthodes | Names |
|---------|----------------|----------|-------|
| index   | `/users` | GET | `users.index` |
| show | `/users/show/{id}` | GET | `users.show` |
| store | `/users/store` | POST | `users.store` |
| update | `/users/update/{id}` | PUT | `users.update` |
| destroy | `/users/destroy/{id}` | DELETE | `users.destroy` |

### Exemple 2 : Utilisation avec un préfixe et des middlewares

Code :
```php
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::auto('/products', \App\Http\Controllers\Admin\ProductController::class);
});
```

Controller :
```php
namespace App\Http\Controllers\Admin;

use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost};

class ProductController extends Controller
{
    #[HttpGet]
    public function index() { /* ... */ }

    #[HttpGet(name: "admin.products.details")]
    public function show(int $id) { /* ... */ }

    #[HttpPost(middleware: ['validate.product'])]
    public function store() { /* ... */ }
}
```


Résultats:
| Actions | Route générées | Méthodes | Names | Middleware |
|---------|----------------|----------|-------|------------|
| index   | `/admin/products` | GET |  `admin.product.index` | `auth, admin` |
| show | `/admin/products/show/{id}` | GET | `admin.products.details` | `auth, admin` |
| store | `/admin/products/store` | POST | `admin.product.store` | `auth, admin, validate.product` |

### Exemple 3 : Personnalisation des URL et des noms

Code :
```php
Route::auto('/blog', \App\Http\Controllers\BlogController::class, [
    'name' => 'blog.',
]);
```

Controller :
```php
use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost};

class BlogController extends Controller
{
    #[HttpGet(url: "articles", name: "list")]
    public function index() { /* ... */ }

    #[HttpGet(url: "article/{slug}", name: "view")]
    public function show(string $slug) { /* ... */ }

    #[HttpPost(url: "new-article")]
    public function create() { /* ... */ }
}
```

Résultats:
| Actions | Route générées | Méthodes | Names |
|---------|----------------|----------|-------|
| index   | `/blog/articles` | GET | `blog.list` |
| show | `/blog/article/{slug}` | GET | `blog.view` |
| create | `/blog/new-article` | POST | `blog.create` |

### Exemple 4 : Gestion des paramètres optionnels

Code :
```php
Route::auto('/api', \App\Http\Controllers\ApiController::class);
```

Controller :
```php
use Florddev\LaravelAutoRouting\Attributes\HttpGet;

class ApiController extends Controller
{
    #[HttpGet]
    public function search(string $query, int $page = 1, string $sort = 'desc') { /* ... */ }
}
```

Route générée :
- GET `/api/search/{query}/{page?}/{sort?}` (name: `api.search`)

### Exemple 5 : Utilisation avec des sous-domaines

Code :
```php
Route::auto('/support', \App\Http\Controllers\SupportController::class, [
    'domain' => 'help.example.com',
]);
```

Controller :
```php
use Florddev\LaravelAutoRouting\Attributes\HttpGet;

class SupportController extends Controller
{
    #[HttpGet]
    public function index() { /* ... */ }

    #[HttpGet]
    public function faq() { /* ... */ }
}
```


Résultats:
| Actions | Route générées | Méthodes | Names |
|---------|----------------|----------|-------|
| index   | `http://help.example.com/support` | GET | `support.index` |
| faq | `http://help.example.com/support/faq` | GET | `support.faq` |

Ces exemples illustrent la flexibilité et la puissance du package Laravel Auto Routing dans différents scénarios. Ils montrent comment le package s'intègre avec les fonctionnalités existantes de Laravel tout en simplifiant la définition des routes.

## Utilisation avec les groupes de routes Laravel

Laravel Auto Routing est conçu pour fonctionner harmonieusement avec les groupes de routes existants de Laravel. Vous pouvez utiliser `Route::auto()` à l'intérieur d'un groupe de routes, et toutes les options du groupe (préfixe, middleware, etc.) seront appliquées aux routes générées automatiquement.

Exemple :

```php
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::auto('/users', UserController::class);
    Route::auto('/products', ProductController::class, ['middleware' => 'admin']);
});
```

Dans cet exemple :
- Toutes les routes générées auront le préfixe '/admin'.
- Toutes les routes auront le middleware 'auth' appliqué.
- Les routes pour `ProductController` auront un middleware supplémentaire 'admin'.

Cette flexibilité vous permet d'intégrer facilement le routage automatique dans votre structure de routes existante, tout en bénéficiant des fonctionnalités de groupage de Laravel.

## Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou à soumettre une pull request.

## Licence

Ce package est open-source et disponible sous la [Licence MIT](https://opensource.org/licenses/MIT).