<?php

namespace Polus;

use Aura\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionMethod;

class Dispatcher implements DispatchInterface
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(Route $route, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $controller = $this->getController($route);
            $methodReflection = $this->getControllerMethod($controller, $route);
        } catch (ReflectionException $re) {
            return $response->withStatus(404);
        }

        return $methodReflection->invokeArgs($controller, $this->getMethodArguments($methodReflection, $route, $request, $response));
    }

    protected function getMethodArguments($methodReflection, Route $route, ServerRequestInterface $request, ResponseInterface $response)
    {
        $attr = $route->attributes;
        $arguments = [];
        foreach ($methodReflection->getParameters() as $param) {
            /* @var $param ReflectionParameter */
            if (isset($attr[$param->getName()])) {
                $arguments[] = $attr[$param->getName()];
            } elseif ($param->getName() === 'response') {
                $arguments[] = $response;
            } elseif ($param->getName() === 'request') {
                $arguments[] = $request;
            } elseif ($param->getName() === 'route') {
                $arguments[] = $route;
            } elseif ($param->getName() === 'app') {
                $arguments[] = $this->app;
            } else {
                $arguments[] = $param->getDefaultValue();
            }
        }
        return $arguments;
    }

    protected function getControllerMethod($controller, Route $route)
    {
        $methodReflection = new ReflectionMethod($controller, $route->handler[1]);
        return $methodReflection;
    }

    protected function getController(Route $route)
    {
        $controller = $this->app->newInstance($route->handler[0]);
        if (method_exists($controller, 'setResponse')) {
            $controller->setResponse($response);
        }
        return $controller;
    }
}
