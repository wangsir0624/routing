<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Routing;


use FastD\Routing\Exceptions\RouteNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RouteCollection
 *
 * @package FastD\Routing
 */
class RouteCollection
{
    const ROUTES_CHUNK = 10;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $middleware = [];

    /**
     * @var Route
     */
    protected $activeRoute;

    /**
     * @var Route[]
     */
    protected $staticRoutes = [];

    /**
     * @var Route[]
     */
    protected $dynamicRoutes = [];

    /**
     * 路由列表计数器
     *
     * @var int
     */
    protected $num = 1;

    /**
     * 路由分组计数器
     *
     * @var int
     */
    protected $index = 0;

    /**
     * @var array
     */
    protected $regexes = [];

    /**
     * @param string $prefix
     * @param callable $callback
     * @param array $middleware
     * @return RouteCollection
     */
    public function group(string $prefix, callable $callback, array $middleware = []): RouteCollection
    {
        $this->middleware = $middleware;

        array_push($this->with, $prefix);

        $callback($this);

        array_pop($this->with);

        $this->middleware = [];

        return $this;
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function get(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('GET', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function post(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('POST', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function put(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('PUT', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function delete(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function head(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('HEAD', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @param string $path
     * @param string $callback
     * @param array $middleware
     * @return Route
     */
    public function patch(string $path, string $callback = '', array $middleware = []): Route
    {
        return $this->addRoute('PATCH', $path, $callback)->withAddMiddleware($middleware);
    }

    /**
     * @return Route
     */
    public function getActiveRoute(): Route
    {
        return $this->activeRoute;
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $callback
     * @return Route
     */
    public function createRoute(string $method, string $path, string $callback = ''): Route
    {
        return new Route($method, $path, $callback);
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $callback
     * @return Route
     */
    public function addRoute(string $method, string $path, string $callback = ''): Route
    {
        $path = implode('/', $this->with) . $path;

        $route = $this->createRoute($method, $path, $callback)->withAddMiddleware($this->middleware);
        unset($path);

        if ($route->isStatic()) {
            return $this->staticRoutes[$method][$route->getPath()] = $route;
        }

        $numVariables = count($route->getVariables());
        $numGroups = max($this->num, $numVariables);
        $this->regexes[$method][] = $route->getRegex() . str_repeat('()', $numGroups - $numVariables);

        $this->dynamicRoutes[$method][$this->index]['regex'] = '~^(?|' . implode('|', $this->regexes[$method]) . ')$~';
        $this->dynamicRoutes[$method][$this->index]['routes'][$numGroups + 1] = $route;

        ++$this->num;

        if (count($this->regexes[$method]) >= static::ROUTES_CHUNK) {
            ++$this->index;
            $this->num = 1;
            $this->regexes[$method] = [];
        }
        unset($numGroups, $numVariables);

        return $route;
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @return Route
     * @throws RouteNotFoundException
     */
    public function match(ServerRequestInterface $serverRequest): Route
    {
        $method = $serverRequest->getMethod();
        $path = $serverRequest->getUri()->getPath();

        if ('/' !== $path) {
            $path = rtrim($path, '/');
        }

        if (isset($this->staticRoutes[$method][$path])) {
            return $this->activeRoute = $this->staticRoutes[$method][$path];
        } else {
            $possiblePath = $path;
            if ('/' === substr($possiblePath, -1)) {
                $possiblePath = rtrim($possiblePath, '/');
            } else {
                $possiblePath .= '/';
            }
            if (isset($this->staticRoutes[$method][$possiblePath])) {
                return $this->activeRoute = $this->staticRoutes[$method][$possiblePath];
            }
            unset($possiblePath);
        }

        return $this->activeRoute = $this->matchDynamicRoute($serverRequest, $method, $path);

    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @param string $method
     * @param string $path
     * @return Route
     */
    protected function matchDynamicRoute(ServerRequestInterface $serverRequest, $method, $path): Route
    {
        if (isset($this->dynamicRoutes[$method])) {
            foreach ($this->dynamicRoutes[$method] as $data) {
                if (!preg_match($data['regex'], $path, $matches)) {
                    continue;
                }
                $route = $data['routes'][count($matches)];
                preg_match('~^' . $route->getRegex() . '$~', $path, $match);
                $match = array_slice($match, 1, count($route->getVariables()));
                $attributes = array_combine($route->getVariables(), $match);
                $attributes = array_filter($attributes);
                $route->mergeParameters($attributes);
                foreach ($route->getParameters() as $key => $attribute) {
                    $serverRequest->withAttribute($key, $attribute);
                }
                return $route;
            }
        }

        throw new RouteNotFoundException($path);
    }
}