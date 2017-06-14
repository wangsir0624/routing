<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

use FastD\Http\Response;
use FastD\Http\ServerRequest;
use FastD\Middleware\Delegate;
use FastD\Routing\Route;
use FastD\Routing\RouteMiddleware;

class Callback extends FastD\Routing\Resource\AbstractResource {

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \FastD\Middleware\DelegateInterface $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Psr\Http\Message\ServerRequestInterface $request, \FastD\Middleware\DelegateInterface $next)
    {
        return (new Response())->withContent('hello');
    }
}

class RouteMiddlewareTest extends PHPUnit_Framework_TestCase
{
    protected $response;

    protected function response()
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }

    public function testRouteMiddleware()
    {
        $middleware = new RouteMiddleware(new Route('GET', '/', 'Callback'));

        $response = $middleware->process(new ServerRequest('GET', '/'), new Delegate(function () {

        }));

        echo $response->getBody();
        $this->expectOutputString('hello');
    }
}
