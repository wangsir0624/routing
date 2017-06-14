<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Routing;

use FastD\Middleware\MiddlewareInterface;


/**
 * Class Route
 *
 * @package FastD\Routing
 */
class Route extends RouteRegex
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $callback;

    /**
     * @var MiddlewareInterface[]
     */
    protected $middleware = [];

    /**
     * Route constructor.
     *
     * @param string $method
     * @param string $path
     * @param string $class
     */
    public function __construct(string $method, string $path, string $class = '')
    {
        parent::__construct($path);

        $this->withMethod($method);

        $this->withCallback($class);
    }

    /**
     * @param array|string $method
     * @return Route
     */
    public function withMethod($method): Route
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $callback
     * @return Route
     */
    public function withCallback($callback): Route
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallback(): string
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return Route
     */
    public function withParameters(array $parameters): Route
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param array $parameters
     * @return Route
     */
    public function mergeParameters(array $parameters): Route
    {
        $this->parameters = array_merge($this->parameters, array_filter($parameters));

        return $this;
    }

    /**
     * @param $middleware
     * @return Route
     */
    public function withMiddleware($middleware): Route
    {
        $this->middleware = [$middleware];

        return $this;
    }

    /**
     * @param $middleware
     * @return Route
     */
    public function withAddMiddleware($middleware): Route
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
