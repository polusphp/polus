<?php

namespace Polus\_Config;

use Aura\Di\Config;
use Aura\Di\Container;
use Polus\Router\Route;
use Polus\Router\AliasRule;
use Aura\Router\Rule;
use Zend\Diactoros\ServerRequestFactory as RequestFactory;

class Common extends Config
{
    const VERSION = '0.0.0';
    public function define(Container $di)
    {
        if (!$di->has('response')) {
            $di->set('response', $di->lazyNew('Zend\Diactoros\Response'));
        }
        if (!$di->has('request')) {
            $di->set('request', RequestFactory::fromGlobals());
        }
        if (!$di->has('router_container')) {
            $di->set('router_container', function () use ($di) {
                $routerContainer = $di->newInstance('Aura\Router\RouterContainer');
                $routerContainer->setRouteFactory(function () {
                    return new Route();
                });
                $routerContainer->getRuleIterator()->set([
                    new Rule\Secure(),
                    new Rule\Host(),
                    new AliasRule(),
                    new Rule\Allows(),
                    new Rule\Accepts(),
                ]);

                return $routerContainer;
            });
        }
        $di->setter['Polus\Traits\ResponseTrait']['setResponse'] = $di->lazyGet('response');
    }
}
