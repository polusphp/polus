<?php

namespace Polus\Middleware;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Polus\DispatchInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    /**
     * @var RouterContainer The router container
     */
    private $router;

    /**
     * @var DispatchInterface Controller dispatcher
     */
    private $dispatcher;

    /**
     * Set the RouterContainer instance.
     *
     * @param RouterContainer $router
     */
    public function __construct(RouterContainer $router, DispatchInterface $dispatcher)
    {
        $this->router = $router;
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
        $matcher = $this->router->getMatcher();
        $route = $matcher->match($request);
        if (!$route) {
            $failedRoute = $matcher->getFailedRoute();
            switch ($failedRoute->failedRule) {
                case 'Aura\Router\Rule\Allows':
                    return $response->withStatus(405); // 405 METHOD NOT ALLOWED
                case 'Aura\Router\Rule\Accepts':
                    return $response->withStatus(406); // 406 NOT ACCEPTABLE
                default:
                    return $response->withStatus(404); // 404 NOT FOUND
            }
        }
        $request = $request->withAttribute('polus:route', $route);
        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = $this->dispatcher->dispatch($route, $request, $response);
        return $next($request, $response);
    }
}
