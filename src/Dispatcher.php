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
            $controller = $this->getController($route, $request, $response);
            $methodReflection = $this->getControllerMethod($controller, $route);
        } catch (ReflectionException $re) {
            return $response->withStatus(404);
        }

        return $methodReflection->invokeArgs($controller, $this->getMethodArguments($methodReflection, $route, $request, $response));
    }

    protected function getMethodArguments($methodReflection, Route $route, ServerRequestInterface $request, ResponseInterface $response)
    {
        $app = $this->app;
        $testVarArray = ['request', 'response', 'route', 'app'];

        $attr = $route->attributes;
        $arguments = [];
        foreach ($methodReflection->getParameters() as $param) {
            $paramClass = $param->getClass();
            if (isset($attr[$param->getName()])) {
                $arguments[] = $attr[$param->getName()];
            } elseif ($paramClass) {
                foreach ($testVarArray as $testVar) {
                    if ($paramClass->isInstance($$testVar)) {
                        $arguments[] = $$testVar;
                    }
                }
            } elseif (in_array($param->getName(), $testVarArray)) {
                $arguments[] = ${$param->getName()};
            } else {
                $arguments[] = $param->getDefaultValue();
            }
        }
        return $arguments;
    }

    protected function getControllerMethod($controller, Route $route)
    {
        $methodName = $route->handler[1];
        if (is_string($route->handler)) {
            $methodName = '__invoke';
        }
        $methodReflection = new ReflectionMethod($controller, $methodName);
        return $methodReflection;
    }

    protected function getController(Route $route, ServerRequestInterface $request, ResponseInterface $response)
    {
        $controllerName = $route->handler[0];
        if (is_string($route->handler)) {
            $controllerName = $route->handler;
        }
        $controller = $this->app->newInstance($controllerName);
        if (method_exists($controller, 'setResponse')) {
            $controller->setResponse($response);
        }
        return $controller;
    }
}
