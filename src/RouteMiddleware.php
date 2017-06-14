<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Routing;


use FastD\Middleware\DelegateInterface;
use FastD\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RouteMiddleware
 * @package FastD\Routing
 */
class RouteMiddleware extends Middleware
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * RouteMiddleware constructor.
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $next
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, DelegateInterface $next = null): ResponseInterface
    {
        $callback = $this->route->getCallback();
        if (false !== strpos($callback, '@')) {
            list($class, $method) = explode('@', $callback);
        } else {
            $class = $callback;
            $method = 'process';
        }
        return call_user_func_array([new $class, $method], [$request, $next]);
    }
}