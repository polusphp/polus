<?php

namespace Polus;

use Aura\Di\Container;
use Aura\Di\Factory;
use Aura\Router\Map;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;
use Zend\Diactoros\Response;

class App extends Container
{
    /**
     * @var Sender
     */
    public $sender;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var RouterContainer
     *
     */
    protected $routerContainer;

    /**
     * @var Map
     */
    protected $map;

    /**
     * @var mixed
     */
    protected $errorHandler;

    /**
     * @var string
     */
    protected $config_dir = '';

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var array
     */
    protected $modeMap = [];

    /**
     * Psr7 middleware queue
     * @var array
     */
    protected $middlewares = [];

    /**
     * Dispatcher
     * @var Polus\DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param string $vendorNs
     * @param string $mode
     */
    public function __construct($vendorNs, $mode = 'production', $request = null)
    {
        parent::__construct(new Factory);
        if (isset($this->modeMap[$mode])) {
            $this->addConfig($this->modeMap[$mode]);
        } else {
            if ($mode == 'development') {
                $this->addConfig($vendorNs . '\_Config\Dev');
            } else {
                $this->addConfig($vendorNs . '\_Config\Production');
            }
        }
        $this->addConfig($vendorNs . '\_Config\Common');
        $this->addConfig('Polus\_Config\Common');

        $this->routerContainer = $this->get('polus:router_container');
        $this->request = $request ?? $this->get('polus:request');
        $this->map = $this->routerContainer->getMap();
        $this->middlewares = $this->get('polus:middlewares');
    }

    public function getContainer()
    {
        return $this;
    }

    /**
     * For testing purpose
     * @param ServerRequestInterface $request [description]
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getMap()
    {
        return $this->map;
    }

    public function getDispatcher()
    {
        if (!$this->dispatcher) {
            $factory = $this->get('polus:dispatcher');
            $this->dispatcher = $factory($this);
        }
        return $this->dispatcher;
    }

    public function addMiddleware(callable $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param string $class
     */
    public function addConfig($class)
    {
        if (class_exists($class)) {
            $config = $this->newInstance($class);
            $config->define($this);
            $this->configs[] = $config;
        }
    }

    /**
     * @param string $controllerClass
     */
    public function registerController($controllerClass)
    {
        if (in_array('Polus\Controller\IController', class_implements($controllerClass))) {
            $methodReflection = new ReflectionMethod($controllerClass, 'registerRoutes');
            $methodReflection->invoke(null, $this->map, $this);
            return true;
        }

        return false;
    }

    /**
     * @param callable $rule
     * @param $position
     * @return boolean
     */
    public function addRouterRule(callable $rule, $position = 'append')
    {
        $ruleIterator = $this->routerContainer->getRuleIterator();
        $ruleIterator->$position($rule);
        return true;
    }

    /**
     * @return void
     */
    public function run()
    {
        $relayBuilder = $this->get('relay');
        $queue = $this->middlewares;
        $queue[] = new Middleware\Dispatcher($this->getDispatcher());
        $relay = $relayBuilder->newInstance($queue);

        $response = new Response();
        $response = $relay($this->request, $response);
    }
}
