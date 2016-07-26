<?php

namespace Polus\Middleware;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Polus\DispatchInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{
    /**
     * @var DispatchInterface Controller dispatcher
     */
    private $dispatcher;

    /**
     * Set the RouterContainer instance.
     *
     * @param RouterContainer $router
     */
    public function __construct(DispatchInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($response->getStatusCode() === 200) {
            $route = $request->getAttribute('polus:route');
            if ($route) {
                $response = $this->dispatcher->dispatch($route, $request, $response);
            }
        }
        return $next($request, $response);
    }
}
