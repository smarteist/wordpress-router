<?php

namespace Hexbit\Router;

use Hexbit\Router\Exceptions\NamedRouteNotFoundException;
use Hexbit\Router\Exceptions\TooLateToAddNewRouteException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    protected static $singleton;

    protected function __construct()
    {
        //singleton
    }

    /**
     * Get the singleton instance of the Router
     *
     * @return RouterBase
     */
    public static function instance(): RouterBase
    {
        if (!isset(static::$singleton)) {
            static::$singleton = new RouterBase();
        }

        return static::$singleton;
    }

    /**
     * Attempt to match the current request against the defined routes
     *
     * If a route matches the Response will be sent to the client and PHP will exit.
     *
     */
    public static function processRequest()
    {
        $response = self::instance()->match();

        if ($response && $response->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            $response->send();
            self::shutdown();
        }

        return;
    }

    /**
     * Match the provided Request against the defined routes and return a Response
     *
     * @param Request $request
     * @return Response
     */
    public static function match(Request $request = null): Response
    {
        return static::instance()->match($request);
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
    public static function map(array $verbs, string $uri, $callback): Route
    {
        return static::instance()->map($verbs, $uri, $callback);
    }

    /**
     * Map a route using the GET method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function get(string $uri, $callback): Route
    {
        return static::instance()->get($uri, $callback);
    }

    /**
     * Map a route using the POST method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function post(string $uri, $callback): Route
    {
        return static::instance()->post($uri, $callback);
    }

    /**
     * Map a route using the PATCH method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function patch(string $uri, $callback): Route
    {
        return static::instance()->patch($uri, $callback);
    }

    /**
     * Map a route using the PUT method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function put(string $uri, $callback): Route
    {
        return static::instance()->put($uri, $callback);
    }

    /**
     * Map a route using the DELETE method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function delete(string $uri, $callback): Route
    {
        return static::instance()->delete($uri, $callback);
    }

    /**
     * Map a route using the OPTIONS method
     *
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     */
    public static function options(string $uri, $callback): Route
    {
        return static::instance()->options($uri, $callback);
    }

    /**
     * Create a Route group
     *
     * @param string $prefix
     * @param callable $callback
     * @return RouterBase
     */
    public static function group(string $prefix, $callback): RouterBase
    {
        return static::instance()->group($prefix, $callback);
    }

    /**
     * Get the URL for a named route
     *
     * @param string $name
     * @param array $params
     * @return string
     * @throws NamedRouteNotFoundException
     */
    public static function url(string $name, $params = [])
    {
        return static::instance()->url($name, $params);
    }

    /**
     * Shutdown PHP
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected static function shutdown()
    {
        exit();
    }
}
