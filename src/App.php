<?php

namespace Polus;

use Aura\Di\Factory;
use Aura\Di\Container;
use Aura\Router\Exception\RouteNotFound;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use ReflectionException;
use ReflectionParameter;

class App extends Container
{
    protected $sender;
    protected $request;
    protected $routerContainer;
    protected $map;
    protected $errorRoutes = [
        'error.400' => 400,
        'error.401' => 401,
        'error.403' => 403,
        'error.404' => 404,
        'error.405' => 405,
        'error.406' => 406,
        'error.500' => 500
    ];
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
        $errorCodesToTrack = [];
        foreach ($this->errorRoutes as $route => $code) {
            try {
                $this->map->getRoute($route);
            } catch (RouteNotFound $rnf) {
                $errorCodesToTrack[] = $code;
            }
        }

        if (count($errorCodesToTrack)) {
            $this->errorHandler = $this->newInstance('Polus\Controller\Error', [
                'codes' => $errorCodesToTrack
            ]);
        }

        $matcher = $this->routerContainer->getMatcher();
        $route = $matcher->match($this->request);

        if (!$route) {
            // get the first of the best-available non-matched routes
            $failedRoute = $matcher->getFailedRoute();
            // which matching rule failed?
            switch ($failedRoute->failedRule) {
                case 'Aura\Router\Rule\Allows':
                    // 405 METHOD NOT ALLOWED
                    // Send the $failedRoute->allows as 'Allow:'
                    if (in_array(405, $errorCodesToTrack)) {
                        return $this->sender->send($this->errorHandler->handler($failedRoute));
                    }
                    $this->dispatch($this->map->getRoute('error.405'));
                    break;
                case 'Aura\Router\Rule\Accepts':
                    if (in_array(404, $errorCodesToTrack)) {
                        return $this->sender->send($this->errorHandler->handler($failedRoute));
                    }
                    $this->dispatch($this->map->getRoute('error.406'));
                    break;
                default:
                    if (in_array(404, $errorCodesToTrack)) {
                        return $this->sender->send($this->errorHandler->handler($failedRoute));
                    }
                    $this->dispatch($this->map->getRoute('error.404'));
                    // 404 NOT FOUND
                    break;
            }
        }
        $this->dispatch($route);
    }

    private function dispatch($route)
    {
        try {
            $controller = $this->newInstance($route->handler[0]);
            $methodReflection = new ReflectionMethod($controller, $route->handler[1]);
            $attr = $route->attributes;
        } catch (ReflectionException $re) {
            try {
                $errorRoute = $this->map->getRoute('error.500');
                $controller = $this->newInstance($errorRoute->handler[0]);
                $methodReflection = new ReflectionMethod($controller, $errorRoute->handler[1]);
                $attr = [
                    'exception' => $re,
                    'route' => $route
                ];
            } catch (RouteNotFound $rnf) {
                return $this->sender->send($this->errorHandler->handler($route, $re));
            }
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

        $response = $methodReflection->invokeArgs($controller, $arguments);
        if (!($response instanceof ResponseInterface)) {
            $newResponse = new \Zend\Diactoros\Response();
            $newResponse->getBody()->write($response);
            $response = $newResponse;
        }
        $this->sender->send($response);
    }
}
