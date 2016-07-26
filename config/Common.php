<?php

namespace Polus\_Config;

use Aura\Di\Config;
use Aura\Di\Container;
use Aura\Router\Rule;
use Polus\Router\AliasRule;
use Polus\Router\Route;
use Zend\Diactoros\ServerRequestFactory as RequestFactory;

class Common extends Config
{
    const VERSION = '1.5.0';
    public function define(Container $di)
    {
        if (!isset($di->params['Polus\Middleware\Router']['router'])) {
            $di->params['Polus\Middleware\Router']['router'] = $di->lazyGet('router_container');
        }
        if (!$di->has('middlewares')) {
            $di->set('middlewares', function () use ($di) {
                $queue = [];
                if (php_sapi_name() !== 'cli') {
                    $queue[] = $di->newInstance('Relay\Middleware\ResponseSender');
                } else {
                    $queue[] = $di->newInstance('Polus\Middleware\CliResponseSender');
                }
                $queue[] = $di->newInstance('Franzl\Middleware\Whoops\Middleware');
                $queue[] = $di->newInstance('Polus\Middleware\Router');
                $queue[] = $di->newInstance('Polus\Middleware\Status404');
                $queue[] = $di->newInstance('Relay\Middleware\FormContentHandler');
                $queue[] = $di->newInstance('Relay\Middleware\JsonContentHandler', [
                    'assoc' => true,
                ]);
                return $queue;
            });
        }
        if (!$di->has('relay')) {
            $di->set('relay', $di->lazyNew('Relay\RelayBuilder'));
        }
        if (!$di->has('response')) {
            $di->set('response', $di->lazyNew('Zend\Diactoros\Response'));
        }

        if (!$di->has('dispatcher')) {
            $di->set('dispatcher', function () use ($di) {
                return function ($app) use ($di) {
                    return $di->newInstance('Polus\Dispatcher', [
                        'app' => $app,
                    ]);
                };
            });
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
    }
}
