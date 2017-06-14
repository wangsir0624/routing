<?php

/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */
class Fuzzy extends \FastD\Routing\Resource\AbstractResource
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \FastD\Middleware\DelegateInterface $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Psr\Http\Message\ServerRequestInterface $request, \FastD\Middleware\DelegateInterface $next)
    {
        return new \FastD\Http\Response($request->getAttribute('path'));
    }
}