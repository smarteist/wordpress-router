<?php

namespace Hexbit\Router;

use AltoRouter;
use Exception;
use Hexbit\Router\Exceptions\NamedRouteNotFoundException;
use Hexbit\Router\Exceptions\TooLateToAddNewRouteException;
use Hexbit\Router\Helpers\Formatting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RouterBase implements Routable
{
    // common routable jobs
    use MethodShortcutsTrait;

    private $routes = [];
    private $altoRouter;
    private $altoRoutesCreated = false;
    private $basePath = '/';

    public function setBasePath($basePath)
    {
        $this->basePath = Formatting::addLeadingSlash(Formatting::addTrailingSlash($basePath));

        // Force the router to rebuild next time we need it
        $this->altoRoutesCreated = false;
    }

    private function addRoute(Route $route)
    {
        if ($this->altoRoutesCreated) {
            throw new TooLateToAddNewRouteException();
        }

        $this->routes[] = $route;
    }

    private function convertUriForAltoRouter(string $uri): string
    {
        return ltrim(preg_replace('/{\s*([a-zA-Z0-9]+\??)\s*}/s', '[:$1]', $uri), ' /');
    }

    /**
     * Map a route
     *
     * @param array $verbs
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     * @throws TooLateToAddNewRouteException
     */
    public function map(array $verbs, string $uri, $callback): Route
    {
        // Force all verbs to be uppercase
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route($verbs, $uri, $callback);

        $this->addRoute($route);

        return $route;
    }

    private function createAltoRoutes()
    {
        if ($this->altoRoutesCreated) {
            return;
        }

        $this->altoRouter = new AltoRouter();
        if (!empty($this->basePath)) {
            $this->altoRouter->setBasePath($this->basePath);
        }
        $this->altoRoutesCreated = true;

        foreach ($this->routes as $route) {
            $uri = $this->convertUriForAltoRouter($route->getUri());

            // Canonical URI with trailing slash - becomes named route if name is provided
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::addTrailingSlash($uri),
                $route->getAction(),
                $route->getName() ?? null
            );

            // Also register URI without trailing slash
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::removeTrailingSlash($uri),
                $route->getAction()
            );
        }
    }

    /**
     * Match the provided Request against the defined routes and return a Response
     *
     * @param Request $request
     * @return Response|bool returns false only if we don't matched anything
     */
    public function match(Request $request = null)
    {
        if (!isset($request)) {
            $request = Request::createFromGlobals();
        }

        $this->createAltoRoutes();

        $altoRoute = @$this->altoRouter->match($request->getRequestUri(), $request->getMethod());

        // Return false if we don't find anything
        if (!isset($altoRoute['target']) || !is_callable($altoRoute['target'])) {
            return false;
        }

        // Call the target with any resolved params
        $params = new RouteParams($altoRoute['params']);
        $response = call_user_func($altoRoute['target'], $params, $request);

        // Ensure that we return an instance of a Response object
        if (!($response instanceof Response)) {
            $response = new Response(
                $response,
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        }

        return $response;
    }

    public function has(string $name)
    {
        $routes = array_filter($this->routes, function ($route) use ($name) {
            return $route->getName() === $name;
        });

        return count($routes) > 0;
    }

    public function url(string $name, $params = [])
    {
        $this->createAltoRoutes();

        try {
            return $this->altoRouter->generate($name, $params);
        } catch (Exception $e) {
            throw new NamedRouteNotFoundException($name, null);
        }
    }

    public function group($prefix, $callback): RouterBase
    {
        $group = new RouteGroup($prefix, $this);

        call_user_func($callback, $group);

        return $this;
    }

}
