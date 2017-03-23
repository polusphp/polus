<?php

namespace Polus;

use Aura\Router\Route;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Dispatcher implements DispatchInterface
{
    /**
     * @var App
     */
    protected $app;

    protected $resolver;

    public function __construct(App $app, DispatchResolverInterface $resolver)
    {
        $this->app = $app;
        $this->resolver = $resolver;
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
            if ($controller === -1) {
                $methodReflection = new ReflectionFunction($route->handler);
                $response = $methodReflection->invokeArgs($this->getMethodArguments($methodReflection, $route, $request, $response));
            } else {
                $methodReflection = $this->getControllerMethod($controller, $route);
                $response = $methodReflection->invokeArgs($controller, $this->getMethodArguments($methodReflection, $route, $request, $response));
            }
        } catch (ReflectionException $re) {
            $response = $response->withStatus(404);
        }
        return $response;
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
        if ($route->handler instanceof Closure) {
            return -1;
        }
        $controllerName = $route->handler[0];
        if (is_string($route->handler)) {
            $controllerName = $route->handler;
        }
        $controller = $this->resolver->resolveController($controllerName);
        if (method_exists($controller, 'setResponse')) {
            $controller->setResponse($response);
        }
        return $controller;
    }
}
