<?php

namespace Polus\Middleware;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    /**
     * @var RouterContainer The router container
     */
    private $router;

    /**
     * Set the RouterContainer instance.
     *
     * @param RouterContainer $router
     */
    public function __construct(RouterContainer $router)
    {
        $this->router = $router;
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
            $request = $request->withAttribute('polus:route', $failedRoute);
            switch ($failedRoute->failedRule) {
                case 'Aura\Router\Rule\Allows':
                    $response = $response->withStatus(405); // 405 METHOD NOT ALLOWED
                case 'Aura\Router\Rule\Accepts':
                    $response = $response->withStatus(406); // 406 NOT ACCEPTABLE
                default:
                    $response = $response->withStatus(404); // 404 NOT FOUND
            }
        } else {
            $request = $request->withAttribute('polus:route', $route);
            foreach ($route->attributes as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }
        return $next($request, $response);
    }
}
