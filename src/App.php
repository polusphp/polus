<?php

namespace Polus;

use Aura\Di\Factory;
use Aura\Di\Container;
use Aura\Router\Exception\RouteNotFound;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use ReflectionException;
use ReflectionParameter;
use Exception as GenericException;

class App extends Container
{
    public $sender;
    protected $request;
    protected $routerContainer;
    protected $map;
    protected $errorHandler;
    protected $config_dir = '';
    protected $configs = [];

    public function __construct($vendorNs)
    {
        parent::__construct(new Factory);
        $host = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'';
        if (strpos($host, 'dev.')===0) {
            $this->addConfig($vendorNs . '\_Config\Dev');
        } elseif (strpos($host, 'staging.')===0) {
            $this->addConfig($vendorNs . '\_Config\Staging');
        } else {
            $this->addConfig($vendorNs . '\_Config\Production');
        }
        $this->addConfig($vendorNs . '\_Config\Common');
        $this->addConfig('Polus\_Config\Common');

        $this->routerContainer = $this->get('router_container');
        $this->request = $this->get('request');

        if ($this->request->hasHeader('content-type')) {
            $contentType = $this->request->getHeader('content-type');
            if (strpos($contentType[0], ';')) {
                $tmp = explode(';', $contentType[0]);
                $contentType = [trim($tmp[0])];
            }
            if ($contentType[0] == "application/json") {
                $payload = (string)$this->request->getBody();
                $this->request = $this->request->withParsedBody(json_decode($payload, true));
            }
        }

        $this->sender = new Sender();
        $this->map = $this->routerContainer->getMap();
    }

    public function errorHandler()
    {
        if (!$this->errorHandler) {
            $this->errorHandler = $this->newInstance('Polus\Controller\Error', [
                'route_map' => $this->map,
                'request' => $this->request,
                'app' => $this
            ]);
        }
        return $this->errorHandler;
    }

    public function addConfig($class)
    {
        $config = $this->newInstance($class);
        $config->define($this);
        $this->configs[] = $config;
    }

    public function registerController($controllerClass)
    {
        if (in_array('Polus\Controller\IController', class_implements($controllerClass))) {
            $methodReflection = new ReflectionMethod($controllerClass, 'registerRoutes');
            $methodReflection->invoke(null, $this->map, $this);
            return true;
        }

        return false;
    }

    public function addRouterRule(callable $rule, $position = 'append')
    {
        $ruleIterator = $this->routerContainer->getRuleIterator();
        $ruleIterator->$position($rule);
        return $true;
    }

    public function run()
    {
        $matcher = $this->routerContainer->getMatcher();
        $route = $matcher->match($this->request);

        if (!$route) {
            $failedRoute = $matcher->getFailedRoute();
            return $this->errorHandler()->dispatch('no_match', [
                'rule' => $failedRoute->failedRule,
                'route' => $failedRoute
            ]);
        }
        $this->dispatch($route);
    }

    public function dispatch($route)
    {
        try {
            $controller = $this->newInstance($route->handler[0]);
            $methodReflection = new ReflectionMethod($controller, $route->handler[1]);
            $attr = $route->attributes;
        } catch (ReflectionException $re) {
            return $this->errorHandler()->dispatch('no_action', [
                'route' => $route,
                'exception' => $re,
                'internal' => $route->internal ? true : false
            ]);
        }

        $arguments = [];
        foreach ($methodReflection->getParameters() as $param) {
            /* @var $param ReflectionParameter */
            if (isset($attr[$param->getName()])) {
                $arguments[] = $attr[$param->getName()];
            } elseif ($param->getName()=='request') {
                $arguments[] = $this->request;
            } elseif ($param->getName()=='route') {
                $arguments[] = $route;
            } elseif ($param->getName()=='app') {
                $arguments[] = $this;
            } else {
                $arguments[] = $param->getDefaultValue();
            }
        }
        try {
            if (method_exists($this, 'preInvoke')) {
                $this->preInvoke($controller, $methodReflection, $arguments);
            }
            $response = $methodReflection->invokeArgs($controller, $arguments);
        } catch (GenericException $ge) {
            return $this->errorHandler()->dispatch('action_exception', [
                'route' => $route,
                'exception' => $ge,
                'internal' => $route->internal ? true : false
            ]);
        }
        if (!($response instanceof ResponseInterface)) {
            $newResponse = new \Zend\Diactoros\Response();
            $newResponse->getBody()->write($response);
            $response = $newResponse;
        }
        $this->sender->send($response);
    }
}
