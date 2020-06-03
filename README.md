# Wordpress Router
![CI](https://travis-ci.org/smarteist/wordpress-router.svg?branch=master)

A simple PHP router built on [SymfonyHttpFoundation](https://github.com/symfony/http-foundation) and [AltoRouter](https://github.com/dannyvankooten/AltoRouter) based on laravel API.
This library is actually built to make routing easier on the [RootsSage](https://github.com/roots/sage) starter framework, however it can also be used in other systems

## Installation

```
composer require hexbit/router
```

## Usage

### Creating Routes

#### Map Methods

Creating a route is done using the `map` function:


In wordpress
```php
// for wordpress projects
use Hexbit\Router\WordPress\Router;

// first init router
add_action("init", function () {
     Router::init();
});

// Creates a route that matches the uri `/posts/list` both GET 
// and POST requests. 
Router::map(['GET', 'POST'], 'posts/list', function () {
    return 'Hello World';
});
```

If you do not use the WordPress system, use the router class below, and [you can attempt to match your current request](#matching-routes-to-requests) in appropriate time.
```php
// use this class for non wordpress systems
use Hexbit\Router\Router;

// Creates a route that matches the uri `/posts/list` both GET 
// and POST requests. 
Router::map(['GET', 'POST'], 'posts/list', function () {
    return 'Hello World';
});
```



`map()` takes 3 parameters:

- `methods` (array): list of matching request methods, valid values:
    + `GET`
    + `POST`
    + `PUT`
    + `PATCH`
    + `DELETE`
    + `OPTIONS`
- `uri` (string): The URI to match against
- `action`  (function|string): Either a closure or a Controller string

#### Route Parameters
Parameters can be defined on routes using the `{keyName}` syntax. When a route matches that contains parameters, an instance of the `RouteParams` object is passed to the action. Second parameter is an instance of `Symfony\Component\HttpFoundation\Request` which contains the information of the current request.

```php
Router::map(['GET'], 'posts/{id}', function(RouteParams $params, Request $request) {
    return $params->id;
});
```

#### Named Routes
Routes can be named so that their URL can be generated programatically:

```php
Router::map(['GET'], 'posts/all', function () {})->name('posts.index');

$url = Router::url('posts.index');
```

If the route requires parameters you can be pass an associative array as a second parameter:

```php
Router::map(['GET'], 'posts/{id}', function () {})->name('posts.show');

$url = Router::url('posts.show', ['id' => 123]);
```

#### HTTP Verb Shortcuts
Typically you only need to allow one HTTP verb for a route, for these cases the following shortcuts can be used:

```php
Router::get('test/route', function () {});
Router::post('test/route', function () {});
Router::put('test/route', function () {});
Router::patch('test/route', function () {});
Router::delete('test/route', function () {});
Router::options('test/route', function () {});
```

#### Setting the basepath
The router assumes you're working from the route of a domain. If this is not the case you can set the base path:

```php
Router::setBasePath('base/path');
Router::map(['GET'], 'route/uri', function () {}); // `/base/path/route/uri`
```

#### Controllers
If you'd rather use a class to group related route actions together you can pass a Controller String to `map()` instead of a closure. The string takes the format `{name of class}@{name of method}`. It is important that you use the complete namespace with the class name.

Example:

```php
// TestController.php
namespace \MyNamespace;

class TestController
{
    public function testMethod()
    {
        return 'Hello World';
    }
}

// routes.php
Router::map(['GET'], 'route/uri', '\MyNamespace\TestController@testMethod');
```

### Creating Groups
It is common to group similar routes behind a common prefix. This can be achieved using Route Groups:

```php
Router::group('api/v1/', function ($group) {
    $group->map(['GET'], 'route1', function () {}); // `/prefix/route1`
    $group->map(['GET'], 'route2', function () {}); // `/prefix/route2§`
});
```

### Matching Routes to Requests
Once you have routes defined, you can attempt to match your current request against them using the `match()` function. `match()` accepts an instance of Symfony's `Request` and returns an instance of Symfony's `Symfony\Component\HttpFoundation\Response`:

```php
// bool|Response
$response = Router::match();

// send response if route matches with current request
if ($response && $response->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            $response->send();
            exit();
}
```

If you return an instance of `Response` from your closure it will be sent back un-touched. If however you return something else, it will be wrapped in an instance of `Response` with your return value as the content.
##### on not found
If no route matches the request, a boolean `false` will be returned as match response.


### Wordpress Virtual Pages

This feature allows you to create template pages on the fly, and there is no need to have that page in the database.

```php
// by default laods page-custom-admin-login.php
$loginPage = new VirtualPage('custom-admin-login', 'Admin Login Title');

Router::virtualPage('login-admin/', $loginPage);
```

Now voila! ```http://yoursite.domain/login-admin``` loads virtual page template.


Contributing
----
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

License
----

MIT
