<?php

namespace Hexbit\Router\Test;

use PHPUnit\Framework\TestCase;
use Hexbit\Router\Exceptions\NamedRouteNotFoundException;
use Hexbit\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Hexbit\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Hexbit\Router\Exceptions\RouteClassStringParseException;
use Hexbit\Router\Exceptions\TooLateToAddNewRouteException;
use Hexbit\Router\Route;
use Hexbit\Router\RouteGroup;
use Hexbit\Router\RouteParams;
use Hexbit\Router\RouterBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RouterTest extends TestCase
{
    /** @test */
    public function map_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->map(['GET'], '/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function map_accepts_lowercase_verbs()
    {
        $router = new RouterBase;

        $route = $router->map(['get', 'post', 'put', 'patch', 'delete', 'options'], '/test/123', function () {});

        $this->assertSame(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->getMethods());
    }

    /** @test */
    public function get_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->get('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function post_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->post('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['POST'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function patch_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->patch('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PATCH'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function put_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->put('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PUT'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function delete_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->delete('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['DELETE'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function options_returns_a_route_object()
    {
        $router = new RouterBase;

        $route = $router->options('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['OPTIONS'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function map_removes_trailing_slash_from_uri()
    {
        $router = new RouterBase;

        $route = $router->map(['GET'], '/test/123/', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function leading_slash_is_optional_when_creating_a_route()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test */
    public function match_returns_a_response_object()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test */
    public function match_does_not_mutate_returned_response_object()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $response = new Response('This is a test', Response::HTTP_ACCEPTED, ['content-type' => 'text/plain']);

        $route = $router->get('/test/123', function () use (&$response) {
            return $response;
        });
        $routerResponse = $router->match($request);

        $this->assertSame($response, $routerResponse);
    }

    /** @test */
    public function match_returns_a_false_response_object_when_route_is_not_found()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;

        $response = $router->match($request);

        $this->assertFalse($response);
    }

    /** @test */
    public function match_works_with_a_closure()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function match_uri_with_trailing_when_route_has_been_defined_without_trailing_slash()
    {
        $request = Request::create('/test/123/', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function match_uri_with_trailing_when_route_has_been_defined_with_trailing_slash()
    {
        $request = Request::create('/test/123/', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function match_uri_without_trailing_when_route_has_been_defined_without_trailing_slash()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function match_uri_without_trailing_when_route_has_been_defined_with_trailing_slash()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;
        $count = 0;

        $route = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function match_works_with_a_class_and_method_string()
    {
        $request = Request::create('/test/123', 'GET');
        $router = new RouterBase;

        $route = $router->get('/test/123', 'Hexbit\Router\Test\Controllers\TestController@returnHelloWorld');
        $response = $router->match($request);

        $this->assertSame('Hello World', $response->getContent());
    }

    /** @test */
    public function match_throws_exception_with_invalid_class_and_string_method()
    {
        $this->expectException(RouteClassStringParseException::class);

        $router = new RouterBase;

        $route = $router->get('/test/123', 'Hexbit\Router\Test\Controllers\TestController:returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_class()
    {
        $this->expectException(RouteClassStringControllerNotFoundException::class);

        $router = new RouterBase;

        $route = $router->get('/test/123', 'Hexbit\Router\Test\Controllers\UndefinedController@returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_method()
    {
        $this->expectException(RouteClassStringMethodNotFoundException::class);

        $router = new RouterBase;

        $route = $router->get('/test/123', 'Hexbit\Router\Test\Controllers\TestController@undefinedMethod');
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function()
    {
        $request = Request::create('/posts/123/comments/abc', 'GET');
        $router = new RouterBase;

        $route = $router->get('/posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function_when_surrounded_by_whitespace()
    {
        $request = Request::create('/posts/123/comments/abc', 'GET');
        $router = new RouterBase;

        $route = $router->get('/posts/{ postId }/comments/{ commentId }', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_generate_canonical_uri_with_trailing_slash_for_named_route()
    {
        $router = new RouterBase;

        $route = $router->get('/posts/all', function () {})->name('test.name');

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function can_generate_canonical_uri_with_trailing_slash_for_named_route_with_params()
    {
        $router = new RouterBase;

        $route = $router->get('/posts/{id}/comments', function () {})->name('test.name');

        $this->assertSame('/posts/123/comments/', $router->url('test.name', ['id' => 123]));
    }

    /** @test */
    public function generating_a_url_for_a_named_route_that_doesnt_exist_throws_an_exception()
    {
        $this->expectException(NamedRouteNotFoundException::class);

        $router = new RouterBase;

        $router->url('test.name');
    }

    /** @test */
    public function can_generate_canonical_uri_after_match_has_been_called()
    {
        $router = new RouterBase;

        $route = $router->get('/posts/all', function () {})->name('test.name');
        $router->match(Request::create('/does/not/match', 'GET'));

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function adding_routes_after_calling_url_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new RouterBase;

        $route = $router->get('posts/all', function () {})->name('test.name');
        $router->url('test.name');

        $route = $router->get('another/url', function () {});
    }

    /** @test */
    public function adding_routes_after_calling_match_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $request = Request::create('posts/all', 'GET');
        $router = new RouterBase;

        $route = $router->get('posts/all', function () {});
        $router->match($request);

        $route = $router->get('another/url', function () {});
    }

    /** @test */
    public function can_add_routes_in_a_group()
    {
        $request = Request::create('/prefix/all', 'GET');
        $router = new RouterBase;
        $count = 0;

        $router->group('prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function group_prefixes_work_with_leading_slash()
    {
        $request = Request::create('/prefix/all', 'GET');
        $router = new RouterBase;
        $count = 0;

        $router->group('/prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function group_prefixes_work_with_trailing_slash()
    {
        $request = Request::create('/prefix/all', 'GET');
        $router = new RouterBase;
        $count = 0;

        $router->group('prefix/', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_add_routes_in_nested_groups()
    {
        $request = Request::create('/prefix/prefix2/all', 'GET');
        $router = new RouterBase;
        $count = 0;

        $router->group('prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->group('prefix2', function ($group) use (&$count) {
                $count++;
                $this->assertInstanceOf(RouteGroup::class, $group);

                $group->get('all', function () {
                    return 'abc123';
                });
            });
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_set_base_path()
    {
        $request = Request::create('/base-path/prefix/all', 'GET');
        $router = new RouterBase;
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_set_base_path_without_trailing_slash()
    {
        $request = Request::create('/base-path/prefix/all', 'GET');
        $router = new RouterBase;
        $router->setBasePath('/base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_set_base_path_without_leading_slash()
    {
        $request = Request::create('/base-path/prefix/all', 'GET');
        $router = new RouterBase;
        $router->setBasePath('base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_set_base_path_without_leading_or_trailing_slash()
    {
        $request = Request::create('/base-path/prefix/all', 'GET');
        $router = new RouterBase;
        $router->setBasePath('base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_update_base_path_after_match_has_been_called()
    {
        $router = new RouterBase;
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $request1 = Request::create('/base-path/prefix/all', 'GET');
        $response1 = $router->match($request1);

        $router->setBasePath('/updated-base-path/');

        $request2 = Request::create('/updated-base-path/prefix/all', 'GET');
        $response2 = $router->match($request2);

        $this->assertSame(2, $count);
        $this->assertSame('abc123', $response1->getContent());
        $this->assertSame('abc123', $response2->getContent());
    }
}
